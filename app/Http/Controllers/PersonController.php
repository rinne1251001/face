<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Services\FaceMatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonController extends Controller
{
    public function __construct(private FaceMatcher $matcher) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $people = Person::query()
            ->withCount('faceSamples')
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('student_number', 'like', "%{$q}%")
                ->orWhere('class_name', 'like', "%{$q}%")))
            ->orderBy('class_name')
            ->orderBy('student_number')
            ->paginate(20)
            ->withQueryString();

        return view('people.index', compact('people', 'q'));
    }

    public function create(): View
    {
        return view('people.form', ['person' => new Person(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $person = Person::create($this->validated($request));

        return redirect()->route('faces.create', $person)
            ->with('status', '人物を登録しました。続けて顔を登録してください。');
    }

    public function edit(Person $person): View
    {
        $person->load(['faceSamples' => fn ($q) => $q->select(['id', 'person_id', 'created_at'])]);

        return view('people.form', compact('person'));
    }

    public function update(Request $request, Person $person): RedirectResponse
    {
        $person->update($this->validated($request, $person));
        $this->matcher->flush();

        return redirect()->route('people.edit', $person)->with('status', '保存しました。');
    }

    public function destroy(Person $person): RedirectResponse
    {
        Storage::disk('local')->deleteDirectory("faces/{$person->id}");
        $person->delete(); // face_samples は外部キー制約で一緒に削除される
        $this->matcher->flush();

        return redirect()->route('people.index')->with('status', '削除しました。');
    }

    private function validated(Request $request, ?Person $person = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'student_number' => ['required', 'string', 'max:20', Rule::unique('people')->ignore($person)],
            'class_name' => ['nullable', 'string', 'max:50'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}