<?php defined('BASEPATH') or exit('No direct script access allowed');

/**
 * CI's Upload library double-checks that a file with an image extension really is an image
 * (getimagesize()), but only for gif/jpg/jpeg/jpe/png/webp. AVIF is accepted as an upload type
 * (config/eshop.php + config/mimes.php), so give it the same check: a renamed non-image must not
 * get in just because its extension is on the list. getimagesize() reads AVIF from PHP 8.1.
 */
class MY_Upload extends CI_Upload
{
    public function is_allowed_filetype($ignore_mime = FALSE)
    {
        if ($this->allowed_types === '*') {
            return TRUE;
        }

        if (empty($this->allowed_types) or !is_array($this->allowed_types)) {
            $this->set_error('upload_no_file_types', 'debug');
            return FALSE;
        }

        $ext = strtolower(ltrim($this->file_ext, '.'));

        if (!in_array($ext, $this->allowed_types, TRUE)) {
            return FALSE;
        }

        // Images get some additional checks
        if (in_array($ext, array('gif', 'jpg', 'jpeg', 'jpe', 'png', 'webp', 'avif'), TRUE) && @getimagesize($this->file_temp) === FALSE) {
            return FALSE;
        }

        if ($ignore_mime === TRUE) {
            return TRUE;
        }

        if (isset($this->_mimes[$ext])) {
            return is_array($this->_mimes[$ext])
                ? in_array($this->file_type, $this->_mimes[$ext], TRUE)
                : ($this->_mimes[$ext] === $this->file_type);
        }

        return FALSE;
    }
}
