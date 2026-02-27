<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersPointExport implements FromQuery, WithHeadings, WithMapping
{
    public function query()
    {
        return User::query()
            ->with('division')
            ->select([
                'id',
                'name',
                'username',
                'division_id',
                'total_point',
            ]);
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->username, // NIM
            $user->division->name ?? '-',
            $user->total_point,
        ];
    }

    public function headings(): array
    {
        return [
            'Nama',
            'NIM',
            'Divisi',
            'Total Point',
        ];
    }
}
