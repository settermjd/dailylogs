<?php

class Logs_View_Helper_SetupEditor
{
    function setupEditor($textareaId)
    {
        return "<script type=\"text/javascript\">CKEDITOR.replace( '". $textareaId ."', {toolbar : 'Basic', height : 200, width: 600} );</script>";
    }
}
