<div class="overflow-x-auto rounded-lg border border-slate-200 dark:border-slate-700">
    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700 text-sm">
        <thead class="bg-slate-100 dark:bg-slate-900/60">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-3 py-2 text-left font-semibold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                        {{ str_replace('_', ' ', $header) }}
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-800 bg-white dark:bg-slate-900">
            @foreach ($rows as $row)
                <tr>
                    @foreach ($headers as $header)
                        <td class="px-3 py-2 text-slate-700 dark:text-slate-200">
                            {{ $row[$header] ?? '—' }}
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
