<?php
/*
Ramadhani
*/

class AttachmentControllerCore extends FrontController
{
    public function postProcess()
    {
        $a = new Attachment(Tools::getValue('id_attachment'), $this->context->language->id);
        if (!$a->id) {
            Tools::redirect('index.php');
        }

        Hook::exec('actionDownloadAttachment', array('attachment' => &$a));

        if (ob_get_level() && ob_get_length() > 0) {
            ob_end_clean();
        }

        header('Content-Transfer-Encoding: binary');
        header('Content-Type: '.$a->mime);
        header('Content-Length: '.filesize(_PS_DOWNLOAD_DIR_.$a->file));
        header('Content-Disposition: attachment; filename="'.mb_convert_encoding($a->file_name, 'ISO-8859-1').'"');
        @set_time_limit(0);
        readfile(_PS_DOWNLOAD_DIR_.$a->file);
        exit;
    }
}
