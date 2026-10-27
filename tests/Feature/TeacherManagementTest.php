<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_list_links_to_edit_and_delete_actions_work(): void
    {
        $this->actingAs(Admin::create([
            'login_id' => 'admin',
            'password' => 'password',
        ]), 'admin');

        $teacher = Teacher::create([
            'name' => '山田花子',
            'login_id' => 'yamada',
            'password' => 'password',
        ]);
        $schoolClass = SchoolClass::create(['name' => '1年A組']);
        $subject = Subject::create([
            'name' => '国語',
            'day_of_week' => 1,
            'period_start' => 1,
            'period_end' => 1,
        ]);
        $teacher->schoolClasses()->attach($schoolClass);
        $teacher->subjects()->attach($subject);

        $index = $this->get(route('admin.teachers.index'));
        $index->assertOk()
            ->assertSee('山田花子')
            ->assertSee('1年A組')
            ->assertSee('国語')
            ->assertSee(route('admin.teachers.edit', $teacher), false)
            ->assertSee(route('admin.teachers.destroy', $teacher), false)
            ->assertSee('name="_method" value="DELETE"', false);

        $this->get(route('admin.teachers.edit', $teacher))
            ->assertOk()
            ->assertSee('教師の編集');

        $this->put(route('admin.teachers.update', $teacher), [
            'name' => '山田太郎',
            'login_id' => 'yamada-t',
            'class_ids' => [$schoolClass->id],
            'subject_ids' => [$subject->id],
        ])->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseHas('teachers', [
            'id' => $teacher->id,
            'name' => '山田太郎',
            'login_id' => 'yamada-t',
        ]);

        $this->delete(route('admin.teachers.destroy', $teacher))
            ->assertRedirect(route('admin.teachers.index'));

        $this->assertDatabaseMissing('teachers', ['id' => $teacher->id]);
    }
}
