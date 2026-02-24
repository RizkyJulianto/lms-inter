<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class AttendanceScanController extends Controller
{
    public function scan(Request $request)
    {
        $request->validate([
            'token' => 'required',
        ]);

        // Cari event
        $event = Event::where('attendance_token', $request->token)->first();

        if (! $event) {
            return response()->json([
                'message' => 'QR tidak valid',
            ], 404);
        }

        // Validasi hari
        if (! now()->isSameDay($event->occasion_date)) {
            return response()->json([
                'message' => 'Absensi hanya bisa di hari event',
            ], 403);
        }

        // Pastikan user sudah join
        $attendance = Attendance::where('event_id', $event->id)
            ->where('user_id', auth()->id())
            ->first();

        if (! $attendance) {
            return response()->json([
                'message' => 'Kamu belum terdaftar di event ini',
            ], 403);
        }

        // Cegah scan dua kali
        if ($attendance->participation_score > 0) {
            return response()->json([
                'message' => 'Kehadiran sudah tercatat',
            ]);
        }

        // Isi point
        $attendance->update([
            'participation_score' => $event->point_reward,
        ]);

        return response()->json([
            'message' => 'Kehadiran berhasil dicatat',
            'point' => $event->point_reward,
        ]);
    }
    public function downloadPng(Event $event)
{
    $svg = QrCode::size(800)
        ->generate($event->attendance_token);

    return response($svg)
        ->header('Content-Type', 'image/svg+xml')
        ->header(
            'Content-Disposition',
            'attachment; filename="qr-event-'.$event->id.'.svg"'
        );
}
}
