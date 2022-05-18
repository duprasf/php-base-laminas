<?php
namespace PublicAsset\Controller;

use Laminas\View\Model\JsonModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Http\Response\Stream;
use Laminas\Http\Headers;

class IndexController extends AbstractActionController
{
    public function IndexAction()
    {
        $assetToLoad = $this->params()->fromRoute('assetToLoad');
        $response = new Stream();
        $headers = new Headers();
        $response->setHeaders($headers);

        $lastModified = filemtime($assetToLoad);
        $etag = md5_file($assetToLoad);

        $headers->addHeaders(array(
            "Last-Modified" => gmdate("D, d M Y H:i:s", $lastModified)." GMT",
            "Etag" => $etag,
        ));

        if(
            (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) == $lastModified
            ) || (
                isset($_SERVER['HTTP_IF_NONE_MATCH'])
                && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag
            )
        ) {
            $response->setStatusCode(304);
        }
        else {
            $mime = array(
                'au'=>'audio/basic',
                'avi'=>'video/msvideo, video/avi, video/x-msvideo',
                'bmp'=>'image/bmp',
                'bz2'=>'application/x-bzip2',
                'css'=>'text/css',
                'csv'=>'text/csv',
                'dtd'=>'application/xml-dtd',
                'doc'=>'application/msword',
                'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'dotx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
                'es'=>'application/ecmascript',
                'exe'=>'application/octet-stream',
                'gif'=>'image/gif',
                'gz'=>'application/x-gzip',
                'hqx'=>'application/mac-binhex40',
                'html'=>'text/html',
                'jar'=>'application/java-archive',
                'jpg'=>'image/jpeg',
                'jpeg'=>'image/jpeg',
                'js'=>'text/javascript',
                'json'=>'application/json',
                'mht'=>'message/rfc822',
                'midi'=>'audio/x-midi',
                'mp3'=>'audio/mpeg',
                'mpeg'=>'video/mpeg',
                'ogg'=>'audio/vorbis, application/ogg',
                'pdf'=>'application/pdf',
                'pl'=>'application/x-perl',
                'png'=>'image/png',
                'potx'=>'application/vnd.openxmlformats-officedocument.presentationml.template',
                'ppsx'=>'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
                'ppt'=>'application/vnd.ms-powerpoint',
                'pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'ps'=>'application/postscript',
                'qt'=>'video/quicktime',
                'ra'=>'audio/x-pn-realaudio, audio/vnd.rn-realaudio',
                'ram'=>'audio/x-pn-realaudio, audio/vnd.rn-realaudio',
                'rdf'=>'application/rdf, application/rdf+xml',
                'rtf'=>'application/rtf',
                'sgml'=>'text/sgml',
                'sit'=>'application/x-stuffit',
                'sldx'=>'application/vnd.openxmlformats-officedocument.presentationml.slide',
                'svg'=>'image/svg+xml',
                'swf'=>'application/x-shockwave-flash',
                'tar.gz'=>'application/x-tar',
                'tgz'=>'application/x-tar',
                'tiff'=>'image/tiff',
                'tsv'=>'text/tab-separated-values',
                'txt'=>'text/plain',
                'wav'=>'audio/wav, audio/x-wav',
                'xlam'=>'application/vnd.ms-excel.addin.macroEnabled.12',
                'xls'=>'application/vnd.ms-excel',
                'xlsb'=>'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
                'xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'xltx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
                'xml'=>'application/xml',
                'zip'=>'application/zip, application/x-compressed-zip',
            );

            $ext = pathinfo($assetToLoad, PATHINFO_EXTENSION);
            $mimeType = isset($mime[$ext]) ? $mime[$ext] : 'application/octet-stream';

            $inline = array('application/pdf', 'message/rfc822', 'text/css', 'text/csv', 'image/gif', 'text/html','image/jpeg', 'text/javascript','application/json','image/png','image/svg+xml','image/tiff','text/tab-separated-values','text/plain',);

            $disposition = in_array($mimeType, $inline) ? 'inline' : 'attachment';

            $response->setStream(fopen($assetToLoad, 'r'));
            $response->setStatusCode(200);
            $response->setStreamName(basename($assetToLoad));
            $headers->addHeaders(array(
                'Content-Disposition' => $disposition.'; filename="' . basename($assetToLoad) .'"',
                'Content-Type' => $mimeType,
                'Content-Length' => filesize($assetToLoad),
                'Expires' => '@0', // @0, because zf2 parses date as string to \DateTime() object
                'Cache-Control' => 'must-revalidate',
                'Pragma' => 'public',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1',
            ));
        }
        return $response;

    }
}