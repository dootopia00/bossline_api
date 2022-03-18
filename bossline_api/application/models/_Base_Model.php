<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class _Base_Model extends CI_Model {
	
	private $dsn;
	private $db_slave, $db_master, $db_atalk, $db_sms, $db_biztalk, $db_search;

	public function __construct()
	{	
		parent::__construct();
		$this->dsn = $this->config->item('dsn');
	}
	
	/**
	* Connect custom db
	* @param $db -> hostname
	*/  
	protected function db_connect($db='')
	{	
		if (!$this->{'db_'.$db})
		{ 	
			$this->{'db_'.$db} = $this->load->database($this->dsn[$db], TRUE);
			// $this->{'db_'.$db}->query("set names utf8");
		}
	}
	
	protected function db_slave()
	{
		return $this->db_slave;
	}
	
	protected function db_master()
	{
		return $this->db_master;
	}
	
	protected function db_search()
	{
		return $this->db_search;
	}

	protected function db_atalk()
	{
		return $this->db_atalk;
	}

	protected function db_biztalk()
	{
		return $this->db_biztalk;
	}

	protected function db_sms()
	{
		return $this->db_sms;
	}



    protected function get_dns()
    {
        return $this->dsn;
    }
}