<?php
$g_show_avatar = ON;
$g_hostname               = '10.1.2.169';
$g_db_type                = 'mysqli';
$g_database_name          = 'bugtracker';
$g_db_username            = 'root';
$g_db_password            = 'diadema';

$g_default_timezone       = 'America/Sao_Paulo';

$g_crypto_master_salt     = 'spnVUg2rzPZH53p87MJQOOutaipzeQnex3xvQSu+eEk=';


$g_log_level = LOG_EMAIL; # | LOG_EMAIL_RECIPIENT | LOG_DATABASE;
$g_log_destination = 'file:/var/www/chamadospmd/mantisbt.log';

/*
$g_manage_user_threshold = REPORT;
$g_create_project_threshold = REPORT;

$g_view_configuration_threshold = REPORT;

$g_set_configuration_threshold = REPORT;

$g_admin_site_threshold = REPORT;
*/
 /****Configuracao de acesso a LDAP parautilizar **********/
 

 $g_login_method = LDAP;
 $g_ldap_protocol_version = 3;
 $g_ldap_server = 'ldap://10.1.2.97';  
 $g_ldap_port = '389';#389 636

 $g_ldap_root_dn = 'dc=diadema,dc=sp,dc=gov,dc=br';          
 $g_ldap_organisation = '(objectClass=*)';

 $g_ldap_uid_field = 'sAMAccountName';
 $g_use_ldap_email = OFF;
 $g_validate_email=OFF;

 $g_use_ldap_realname    = ON;
 $g_ldap_bind_dn = 'cn=Administrator,cn=Users,dc=diadema,dc=sp,dc=gov,dc=br';
 $g_ldap_bind_passwd ='$di@d2016!';

 /******** Fim Configuracao de acesso a LDAP  **********/

/********************CONFIGURAÇAO DE EMAIL************************/
/*
# $g_phpMailer_method = 2;
 $g_phpMailer_method = PHPMAILER_METHOD_SMTP;
# $g_smtp_host = 'correio.diadema.sp.gov.br';#'10.1.2.88';
 $g_smtp_host = '10.1.2.88';


 # Alterado em 26/05/2011 - Jad - Anderson
 # Alterado em 24/07/2014 - Jad - Amanda/Maiara
 $g_administrator_email = 'suporte_programas@diadema.sp.gov.br';
 $g_webmaster_email = 'suporte_programas@diadema.sp.gov.br';
 # the "From: " field in emails
 $g_from_email = 'suporte_programas@diadema.sp.gov.br';
 $g_from_name = '[TESTE NOVO MANTIS 2.14]';
 # the return address for bounced mail
 $g_return_path_email = 'suporte_programas@diadema.sp.gov.br';

 $g_smtp_connection_mode = 'tls';
 $g_smtp_port = 587;

 $g_smtp_username           = 'suporte_programas';
 $g_smtp_password           = 'diadema@10';

 $g_allow_blank_email = ON;
 $g_enable_email_notification = ON;
 $g_validate_email = OFF;
# $g_handle_bug_threshold = REPORTER;

#$g_notify_flags['feedback']['threshold_min'] = DEVELOPER;
#$g_notify_flags['feedback']['threshold_max'] = DEVELOPER;

 $g_destination         = 'file:/var/log/chamadospmd/mantisbt.log';
*/
/*******************FIM CONFIGURAÇO DE E-MAIL*********************/


$g_allow_signup	 = ON;  //allows the users to sign up for a new account
$g_enable_email_notification = ON; //enables the email messages
$g_phpMailer_method = PHPMAILER_METHOD_SMTP;
$g_smtp_host = '10.1.2.88';
$g_smtp_connection_mode = 'tls';
$g_smtp_port = 587;
$g_smtp_username = 'suporte_programas@diadema.sp.gov.br'; 
$g_smtp_password = 'diadema@10'; //replace it with your gmail password
$g_administrator_email = 'suporte_programas@diadema.sp.gov.br'; //this will be your administrator email address

	#####################
	# Wiki Integration
	#####################

	# Wiki Integration Enabled?
	$g_wiki_enable = ON;
 
	# Wiki Engine
	#$g_wiki_engine = 'pmdwiki';
 
	# Wiki namespace to be used as root for all pages relating to this mantis installation.
	#$g_wiki_root_namespace = 'pmdwiki';
 
	# URL under which the wiki engine is hosted.  Must be on the same server.
	#$g_wiki_engine_url = $t_protocol . '://' . $t_host . '/%wiki_engine%/';


        #$g_wiki_engine_url = 'http://pmdwiki.diadema.sp.gov.br/';
        #$g_cookie_domain = '.diadema.sp.gov.br'; 




   #################################

   #	DOCUMENT         

   #################################


$g_enable_project_documentation = ON;



$g_show_project_menu_bar = OFF;
