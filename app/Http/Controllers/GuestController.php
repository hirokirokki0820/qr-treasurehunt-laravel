<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * 参加していない（またはログアウトした）ゲスト向けのページ。
 * 主催者向けの案内は出さず、参加用QRコードを読み取るスキャナーだけを置く。
 */
class GuestController extends Controller
{
    /**
     * ゲスト用のトップページ。参加前の人や、宝探しをやめた人に表示する。
     */
    public function index(): View
    {
        return view('guest.index', [
            'heading' => "QRコードを読み取って\n参加しよう",
            'lead' => '主催者が用意した「参加用QRコード」をカメラで読み取ると、宝探しに参加できます。',
        ]);
    }

    /**
     * 参加していない状態で、参加者用のページや宝のQRコードを開いたときのページ。
     */
    public function required(): View
    {
        return view('guest.index', [
            'heading' => 'まずは参加受付をしてね',
            'lead' => '主催者が用意した「参加用QRコード」を読み取って、ニックネームを登録してください。',
        ]);
    }
}
