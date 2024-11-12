<!-- resources/views/filament/forms/components/view-flow-responsabili.blade.php -->
<table style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr>
            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Etapă</th>
            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Persoana principală</th>
            <th style="text-align: left; padding: 8px; border-bottom: 2px solid #ddd;">Persoana secundară</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($stages as $stage)
            <tr>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">{{ $stage->stage_number }}</td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <span style="
                        display: inline-block;
                        background-color: #cdf7e1; /* Verde deschis */
                        color: #698f84; /* Verde inchis */
                        padding: 4px 8px;
                        border-radius: 9999px;
                        font-size: 12px;
                        font-weight: 500;
                    ">
                        {{ \App\Models\User::find($stage->principal_person)?->name ?? 'N/A' }}
                    </span>
                </td>
                <td style="padding: 8px; border-bottom: 1px solid #ddd;">
                    <span style="
                        display: inline-block;
                        background-color: #f3f4f6; 
                        color: #6b7280; 
                        padding: 4px 8px;
                        border-radius: 9999px;
                        font-size: 12px;
                        font-weight: 500;
                    ">
                        {{ \App\Models\User::find($stage->second_person_name)?->name ?? 'N/A' }}
                    </span>
                </td>
            </tr>
        @endforeach
    </tbody>
</table>