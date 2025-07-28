<?php
/*
Ramadhani
*/
$dir = Context::getContext()->smarty->getTemplateDir(0).'controllers'.DIRECTORY_SEPARATOR.trim($con->override_folder, '\\/').DIRECTORY_SEPARATOR;
$footer_tpl = file_exists($dir.'footer.tpl') ? $dir.'footer.tpl' : 'footer.tpl';
echo Context::getContext()->smarty->fetch($footer_tpl);
