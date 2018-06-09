<?php

namespace App\Http\Controllers;

use App\Models\Share;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function show(Request $request, $code)
    {
        $share = Share::query()->where('code', $code)->firstOrFail();
        $share->increment('read_count');
        $link = $share->link;

        return view('share', [
            'share' => $share,
            'link' => $link
        ]);
    }
}
