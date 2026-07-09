<html>
<body>
    <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
            <tr>
                <td bgcolor="#f2f2f2" style="font-size:0px">&nbsp;</td>
                <td bgcolor="#ffffff" width="660" align="center">
                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tbody>
                            <tr>
                                <td align="center" width="600" valign="top">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tbody>
                                            <tr><td bgcolor="#f2f2f2" style="padding-top:10px"></td></tr>
                                            <tr><td bgcolor="#f2f2f2" style="padding-top:10px"></td></tr>
                                            <tr>
                                                <td align="center" valign="top" bgcolor="#ffffff">
                                                    <table border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;padding-top:20px" width="100%">
                                                        <tbody>
                                                            <tr valign="bottom">
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                                <td>
                                                                    <center>
                                                                        <p><img src="{{ asset('assets/logo/'.$logo) }}" height="50" alt="logo"></p>
                                                                    </center>
                                                                </td>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;padding-top:10px;margin-bottom:10px" width="100%">
                                                        <tbody>
                                                            <tr valign="bottom">
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                                <td valign="top" style="font-family:Calibri,Trebuchet,Arial,sans serif;font-size:15px;line-height:22px;color:#333333">
                                                                    <p>Hai {{ $nama }}</p>
                                                                    <p style="color:#455056; font-size:15px;line-height:24px; margin:0;">
                                                                        Akun anda saat ini telah aktif, dengan info akun : <br>
                                                                        Email : {{ $email }} <br/>
                                                                        Password : {{ $password }} <br/><br/>
                                                                        silahkan login pada website klik tombol dibawah ini
                                                                    </p>
                                                                    <a href="{{ url('/') }}"
                                                                        style="background:#4054B2;text-decoration:none !important; font-weight:500; margin-top:35px; color:#fff;text-transform:uppercase; font-size:14px;padding:10px 24px;display:inline-block;border-radius:50px;">Login Sekarang !</a>
                                                                    <p><br><br>Best Regards,<br/><strong>{{ $titletext }}</strong><br>
                                                                </td>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <table cellpadding="0" cellspacing="0" border="0" width="100%">
                        <tbody>
                            <tr>
                                <td align="center" width="600" valign="top">
                                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                        <tbody>
                                            <tr><td bgcolor="#f2f2f2" style="padding-top:20px"></td></tr>
                                            <tr>
                                                <td align="center" valign="top" bgcolor="#f2f2f2">
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tbody>
                                                            <tr valign="bottom">
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                                <td style="font-family:Calibri,Trebuchet,Arial,sans serif;font-size:13px;color:#666;font-weight:bold">
                                                                    <div style="margin:5px 0;padding:0">
                                                                        <a href="{{ url('/') }}" style="text-decoration:none" target="_blank">Bantuan&nbsp;</a>
                                                                        <span style="color:#ccc"> | </span>
                                                                        <a href="{{ url('/') }}" style="text-decoration:none" target="_blank">Website&nbsp;</a>
                                                                    </div>
                                                                </td>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                                        <tbody>
                                                            <tr valign="bottom">
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                                <td>
                                                                    <p>Jangan balas ke email ini. Untuk menghubungi kami, klik <strong><a href="" style="text-decoration:none" target="_blank">Bantuan dan Hubungi</a></strong>.</p>
                                                                </td>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                    <table border="0" cellpadding="0" cellspacing="0" style="padding-top:10px;font:12px Arial,Verdana,Helvetica,sans-serif;color:#292929" width="100%">
                                                        <tbody>
                                                            <tr>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                                <td><p>Hak Cipta © {{ date('Y') }} {{ $titletext }}</p></td>
                                                                <td width="20" align="center" valign="top">&nbsp;</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
                <td bgcolor="#f2f2f2" style="font-size:0px">&nbsp;</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
