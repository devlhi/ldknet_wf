<?php

function bulan_indo($ambil)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];
    $split = explode('-', $ambil);

    return ' '.$bulan[(int) $split[1]].' '.$split[0];
}
