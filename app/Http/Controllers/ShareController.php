<?php

namespace App\Http\Controllers;

use App\Share;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    public function show(Request $request, $code)
    {
        $share = Share::where('code', $code)->firstOrFail();
        $share->readCount++;
        $share->save();
        $link = $share->link;

        return view('share', [
            'link' => $link
        ]);
    }
}
