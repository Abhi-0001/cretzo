<?php
class Migrate extends CI_Controller{
    public function index(){
		// is_cli() so a deploy can run `php index.php admin migrate` without an interactive
		// admin login. CI routes CLI invocations through the same controllers, and a CLI
		// caller already has shell access to the server, so this grants nothing new - it
		// just removes the requirement to log into the admin panel to apply schema changes.
        if (is_cli() || ($this->ion_auth->logged_in() && $this->ion_auth->is_admin())) {
			$this->load->library('migration');
			if ($this->migration->latest() === FALSE) {
				if (is_cli()) {
					echo "Migration FAILED: " . $this->migration->error_string() . PHP_EOL;
					exit(1);
				}
				show_error($this->migration->error_string());
			}else{
				echo "Migration Successfully" . (is_cli() ? PHP_EOL : '');
			}
		}else{
			echo "You are not authorized to do this";
		}
    }
    public function rollback($version = ''){
        if ($this->ion_auth->logged_in() && $this->ion_auth->is_admin() && defined('ALLOW_MODIFICATION') && ALLOW_MODIFICATION == 1) {
			$this->load->library('migration');
			if(!empty($version) && is_numeric($version)){
				$this->migration->version($version);
			}else{
				show_error($this->migration->error_string());
			}
		}else{
			echo "You are not authorized to do this";
		}
    }
}
