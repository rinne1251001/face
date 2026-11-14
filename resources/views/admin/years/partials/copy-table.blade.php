{{-- 年度更新の「クラスコピー」「授業コピー」の表（どちらも同じ形なので共通にしている）
     $field … 送信するときの名前（'classes' か 'subjects'）
     $label … 名前の列の見出し
     $rows  … 最初から出しておく行（['copy' => 1, 'name' => '1T1', 'grade' => 1] の配列） --}}
<div class="ay-table-container">
    <table data-copy-table="{{ $field }}">
        <thead>
            <tr><th>コピー</th><th>{{ $label }}</th><th>学年</th></tr>
        </thead>
        <tbody>
            @foreach ($rows as $i => $row)
                <tr>
                    <td>
                        {{-- added … この行は送信の対象（予備の行と区別するための印） --}}
                        <input type="hidden" name="{{ $field }}[{{ $i }}][added]" value="1">
                        <input type="checkbox" name="{{ $field }}[{{ $i }}][copy]" value="1" @checked(! empty($row['copy'])) aria-label="コピーする">
                    </td>
                    <td>
                        <input type="text" name="{{ $field }}[{{ $i }}][name]" value="{{ $row['name'] ?? '' }}" maxlength="{{ $maxLength }}" aria-label="{{ $label }}">
                    </td>
                    <td>
                        <select name="{{ $field }}[{{ $i }}][grade]" aria-label="学年">
                            @for ($g = 1; $g <= $maxGrade; $g++)
                                <option value="{{ $g }}" @selected((int) ($row['grade'] ?? 1) === $g)>{{ $g }}年</option>
                            @endfor
                        </select>
                    </td>
                </tr>
            @endforeach

            {{-- 予備の行（「追加」を押した行だけ、added が送信されて登録の対象になる） --}}
            @for ($i = count($rows); $i < count($rows) + 10; $i++)
                <tr class="add-row">
                    <td class="add-cell" colspan="3">
                        <label class="add-btn"><input type="checkbox" class="add-toggle" name="{{ $field }}[{{ $i }}][added]" value="1">追加</label>
                    </td>
                    <td>
                        <input type="checkbox" name="{{ $field }}[{{ $i }}][copy]" value="1" checked aria-label="登録する">
                    </td>
                    <td>
                        <input type="text" name="{{ $field }}[{{ $i }}][name]" maxlength="{{ $maxLength }}" aria-label="{{ $label }}">
                    </td>
                    <td>
                        <select name="{{ $field }}[{{ $i }}][grade]" aria-label="学年">
                            @for ($g = 1; $g <= $maxGrade; $g++)
                                <option value="{{ $g }}">{{ $g }}年</option>
                            @endfor
                        </select>
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>
    @foreach (collect($errors->get($field.'*'))->flatten()->unique() as $message)
        <p class="ay-error">{{ $message }}</p>
    @endforeach
</div>