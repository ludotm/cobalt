<?php 

namespace Application\Base\Files;

use Application\Base\Controllers\ExtendedController;
use Core\Service;

class FilesController extends ExtendedController
{

	public function onDispatch()
	{

	}

	public function downloadAction () 
	{
        //- turn off compression on the server
        @apache_setenv('no-gzip', 1);
        @ini_set('zlib.output_compression', 'Off');
        
        //var_dump($this->request->get->file); exit();

        $file = realpath(ROOT . '/Files/carte-du-monde.jpg');
        //$file = realpath(ROOT . '/Files/avatar.png');
        //$file = "http://www.total-manga.com/openX/www/images/d765c3a34a48a7b0bde1cf6a8e5cb1e1.png";

        $stream = false; // false > force downlad, true > affiche

        // sanitize the file request, keep just the name and extension
        // also, replaces the file location with a preset one ('./myfiles/' in this example)
        $file_path  = $file;
        $path_parts = pathinfo($file_path);
        $file_name  = $path_parts['basename'];
        $file_ext   = $path_parts['extension'];
        //$file_path  = './myfiles/' . $file_name;


        // allow a file to be streamed instead of sent as an attachment
        $is_attachment = !$stream;

        //if (true)
        if (is_file($file_path)) {
            //$file_size = $this->get_distant_filesize($file_path);

            $file_size  = filesize($file_path);
            $file = @fopen($file_path,"rb");

            if ($file) {
                // set the headers, prevent caching
                header("Pragma: public");
                header("Expires: -1");
                header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
                header("Content-Disposition: attachment; filename=\"$file_name\"");
         
                // set appropriate headers for attachment or streamed file
                if ($is_attachment) {
                    header("Content-Disposition: attachment; filename=\"$file_name\"");
                } else {
                    header('Content-Disposition: inline;');
                }
                        
                // set the mime type based on extension, add yours if needed.
                $ctype_default = "application/octet-stream";
                $content_types = array(
                        "exe" => "application/octet-stream",
                        "zip" => "application/zip",
                        "mp3" => "audio/mpeg",
                        "mpg" => "video/mpeg",
                        "avi" => "video/x-msvideo",
                        "png" => "image/png",
                        "gif" => "image/gif",
                        "jpg" => "image/jpeg",
                        "jpeg" => "image/jpeg",
                        "ptt" => "application/vnd.ms-powerpoint",
                        "xls" => "application/vnd.ms-excel",
                        "doc" => "application/msword",
                );

                $ctype = isset($content_types[$file_ext]) ? $content_types[$file_ext] : $ctype_default;
                header("Content-Type: " . $ctype);
         
                //check if http_range is sent by browser (or download manager)
                if(isset($_SERVER['HTTP_RANGE'])) {
                    list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                    if ($size_unit == 'bytes') {
                        //multiple ranges could be specified at the same time, but for simplicity only serve the first range
                        //http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
                        list($range, $extra_ranges) = explode(',', $range_orig, 2);
                    } else {
                        $range = '';
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        exit;
                    }
                }
                else {
                    $range = '';
                }
         
                //figure out download piece from range (if set)
                $ranges = explode('-', $range, 2);
                list($seek_start, $seek_end) = count($ranges) == 1 ? array($ranges[0] , null) : $ranges ;

                //set start and end based on range (if set), else set defaults
                //also check for invalid ranges.
                $seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)),($file_size - 1));
                $seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);
         
                //Only send partial content header if downloading a piece of the file (IE workaround)
                if ($seek_start > 0 || $seek_end < ($file_size - 1)) {
                    header('HTTP/1.1 206 Partial Content');
                    header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
                    header('Content-Length: '.($seek_end - $seek_start + 1));
                }
                else {
                    header("Content-Length: $file_size");
                }
                  
         
                header('Accept-Ranges: bytes');
         
                set_time_limit(0);
                fseek($file, $seek_start);
         
                while(!feof($file)) {
                    print(@fread($file, 1024*8));
                    ob_flush();
                    flush();
                    if (connection_status()!=0) {
                        @fclose($file);
                        exit;
                    }           
                }
         
                // file save was a success
                @fclose($file);
                exit;

            } else {
                // file couldn't be opened
                header("HTTP/1.0 500 Internal Server Error");
                exit;
            }
        }
        else {
            // file does not exist
            header("HTTP/1.0 404 Not Found");
            exit;
        }
	}



    protected function get_distant_filesize($url) 
    {
        $my_ch = curl_init();
        curl_setopt($my_ch, CURLOPT_URL,$url);
        curl_setopt($my_ch, CURLOPT_HEADER,         true);
        curl_setopt($my_ch, CURLOPT_NOBODY,         true);
        curl_setopt($my_ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($my_ch, CURLOPT_TIMEOUT,        10);
        $r = curl_exec($my_ch);
         foreach(explode("\n", $r) as $header) {
            if(strpos($header, 'Content-Length:') === 0) {
                return trim(substr($header,16)); 
            }
         }
        return '';
    }

    public function downloadVideoAction()
    {

    }

    public function fileAction ()
    {
        
    }

    public function imageAction ()
    {
        $config = Service::Config():
        $upload_path = $config->get('path/upload_path');

        $format = $this->request->get->format;
        $image_id = $this->request->get->image_id;
        
        $path = $upload_path; 

        if ($path === false || !file_exists($path)) {
            $response->setStatusCode(404);
            return $this->getResponse();
        }

        $headers->addHeaderLine('Cache-Control', 'public');
        $headers->addHeaderLine('Pragma', 'public');
        $headers->addHeaderLine('Content-type', $file->mime_type);
        $headers->addHeaderLine('Content-Disposition', 'inline; filename="' . $file->name . '"');

        echo file_get_contents($path);
        exit();
    }

}