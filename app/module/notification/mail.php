<?php
class Module_Notification_Mail
{
    /**
     * @return string
     */
    static function _common_footer()
    {
        $conf = Da\Sys_Config::config('env/http');
        $domain = $conf['domain'];
        $msg = '<p>';
        $msg .= '<span style="color:gray;">来自';
        $msg .= '<a href="http://'.$domain.'" style="font-weight:bold;color:white;background:#1982d1;text-decoration:none;padding:0.3em;border-radius:0.3em;">';
        $msg .= '数据接入-- Data Access';
        $msg .= '</a>';
        $msg .= '</span>';
        $msg .= '<span style="color:gray;">于<span style="color:green;font-weight:bold;">';
        $msg .= date("Y-m-d H:i:s").'</span>';
        $msg .= '</span>';
        $msg .= '</p>';
        return $msg;
    }

    /**
     * @param string $mail_to
     * @param string $title
     * @param string $msg
     */
    static function send_mail($mail_to, $title, $msg)
    {
        $headers = 'MIME-Version: 1.0' . "\n";
        $headers .= 'Content-type: text/html; charset=uft-8' . "\r\n";
        $headers .="Content-Transfer-Encoding: 8bit";
        $msg = '<div style="padding:1em;border-left:3px solid #1982d1;">'.$msg.'</div>';
        $msg .= self::_common_footer();
        Lib_Helper::send_mail($mail_to, $msg, Lib_Helper::encodeMIMEString('UTF8', $title), $headers);
    }
}