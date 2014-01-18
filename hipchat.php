#!/usr/bin/env php
<?php
namespace websharks\hipchat_cli;

new message(); // Construct instance handler.

class message // Message class; constructor is handler.
{
	public $short_ops = 't::f::c::r::F::m::n::v::h::';

	public $long_ops = array('token::', 'from::', 'color::', 'room::', 'format::', 'message::', 'notify::', 'version::', 'help::');

	public $endpoint = 'https://api.hipchat.com/v1'; // See: <https://www.hipchat.com/docs/api/method/rooms/message>

	public $version = '140118'; // CLI version string.

	public function __construct() // Constructor is handler.
		{
			ini_set('display_errors', TRUE); // Display errors.

			$this->ops = // Command-line options.
				getopt($this->short_ops, $this->long_ops);

			$default_token = ''; // Initialize.
			if(!empty($_SERVER['HIPCHAT_CLI_TOKEN']))
				$default_token = $_SERVER['HIPCHAT_CLI_TOKEN'];

			$default_from = ''; // Initialize.
			if(!empty($_SERVER['HIPCHAT_CLI_MSG_FROM']))
				$default_from = $_SERVER['HIPCHAT_CLI_MSG_FROM'];

			$default_color = 'random'; // Initialize.
			if(!empty($_SERVER['HIPCHAT_CLI_MSG_COLOR']))
				$default_color = $_SERVER['HIPCHAT_CLI_MSG_COLOR'];

			$default_room = ''; // Initialize.
			if(!empty($_SERVER['HIPCHAT_CLI_MSG_ROOM']))
				$default_room = $_SERVER['HIPCHAT_CLI_MSG_ROOM'];

			$default_format = 'markdown'; // Initialize.
			if(!empty($_SERVER['HIPCHAT_CLI_MSG_FORMAT']))
				$default_format = $_SERVER['HIPCHAT_CLI_MSG_FORMAT'];

			$default_message = ''; // Initialize.

			if(defined('STDIN') && is_resource(STDIN))
				{
					stream_set_blocking(STDIN, 0);
					// ^ Don't wait if data isn't here now.
					$default_message = stream_get_contents(STDIN);
				}
			if(!$default_message && count($GLOBALS['argv']) > 1) // Last arg value.
				$default_message = $GLOBALS['argv'][count($GLOBALS['argv']) - 1];

			$this->satisfy_op('t', 'token', $default_token);
			$this->satisfy_op('f', 'from', $default_from);
			$this->satisfy_op('c', 'color', $default_color);
			$this->satisfy_op('r', 'room', $default_room);
			$this->satisfy_op('F', 'format', $default_format);
			$this->satisfy_op('m', 'message', $default_message);
			$this->satisfy_op('n', 'notify', NULL); // Set indicates yes.
			$this->satisfy_op('v', 'version', NULL); // Set indicates yes; version.
			$this->satisfy_op('h', 'help', NULL); // Set indicates yes; help page.

			$this->parse_markdown(); // Possibly.

			if(isset($this->ops['version'])) $this->version();
			if(isset($this->ops['help'])) $this->usage_help();

			if(!$this->ops['token'] || !$this->ops['from'] || !$this->ops['room'] || !$this->ops['message'])
				$this->usage_help(TRUE); // Unable to process; show help page.

			$this->send(); // Attempt to send the message now.
		}

	public function satisfy_op($short_op, $long_op, $default_value = NULL)
		{
			if(isset($this->ops[$long_op]))
				return $this->ops[$long_op];

			if(isset($this->ops[$short_op]))
				return ($this->ops[$long_op] = $this->ops[$short_op]);

			return ($this->ops[$long_op] = $default_value);
		}

	public function is_markdown()
		{
			if(!$this->ops['message'])
				return FALSE;

			if($this->ops['format'] !== 'markdown')
				return FALSE;

			if(preg_match('/^\/\w+\s/', $this->ops['message']))
				return FALSE; // Ignore `/slash` commands.

			if(preg_match('/(?:^|\s)@\w/', $this->ops['message']))
				return FALSE; // Ignore `@callouts`.

			return TRUE; // Markdown.
		}

	public function parse_markdown()
		{
			if(!$this->is_markdown())
				{
					if($this->ops['format'] === 'markdown')
						$this->ops['format'] = 'text';
					return; // All done.
				}
			static $wfm_parser; // Static instance; i.e. singleton.
			if(!isset($wfm_parser)) // Don't have this yet?
				{
					require_once dirname(__FILE__).'/externals/markdown-x.php';
					$wfm_parser = new \websharks_core_v000000_dev\externals\markdown_x();
				}
			$this->ops['format']  = 'html'; // Message becomes HTML now.
			$this->ops['message'] = $wfm_parser->transform($this->ops['message']);
			if(strpos($this->ops['message'], '<') === FALSE)
				$this->ops['message'] = 'text';
		}

	public function send()
		{
			$response = $this->curl($this->endpoint.'/rooms/message',
			                        array('auth_token' => $this->ops['token'],
			                              'room_id'    => $this->ops['room'], 'from' => $this->ops['from'],
			                              'message'    => $this->ops['message'], 'message_format' => $this->ops['format'],
			                              'notify'     => (isset($this->ops['notify'])) ? '1' : '0', 'color' => $this->ops['color']),
			                        20, 20, array(), '', FALSE);

			if(!$response || !is_object($response = json_decode($response)))
				$this->output('Unknown message delivery failure.', 1);

			if(empty($response->status) || $response->status !== 'sent')
				$this->output('Delivery failure The HipChat API returned the following...'."\n".print_r($response, TRUE), 2);

			$this->output('', 0); // Success.
		}

	public function version()
		{
			$this->output($this->version, 0);
		}

	public function usage_help($invalid = FALSE)
		{
			if($invalid) // In response to invalid args?
				$help[] = 'Invalid argument(s); please try again.'."\n\n";

			$help[] = '~~~~~~~~~~ HipChat CLI Help/Documentation ~~~~~~~~~~'."\n\n";

			$help[] = "\t".'This command-line tool sends messages to a HipChat room. This tool currently supports all possible options that are made available in version 1 of the HipChat API. For further details, please see: <https://www.hipchat.com/docs/api/method/rooms/message>'."\n\n";

			$help[] = 'USAGE:'."\n\n";

			$help[] = "\t".'$ hipchat [options] [message]'."\n\n";

			$help[] = 'EXAMPLE (MINIMUM REQUIRED ARGUMENTS):'."\n\n";

			$help[] = "\t"."$ hipchat --token='akaxlsdow234er443ssdlskdoeeesdfls9434' --from='John' --room='555555' --message='Hello world!'"."\n\n";

			$help[] = 'EXAMPLE (MESSAGE AS LAST ARGUMENT INSTEAD OF PASSING --message OPTION):'."\n\n";

			$help[] = "\t"."$ hipchat --token='akaxlsdow234er443ssdlskdoeeesdfls9434' --from='John' --room='555555' 'Hello world!'"."\n\n";

			$help[] = 'ENVIRONMENT VARIABLES (OPTIONAL):'."\n\n";

			$help[] = "\t"."$ export HIPCHAT_CLI_TOKEN='[YOUR HIPCHAT API TOKEN]';"."\n";
			$help[] = "\t"."$ export HIPCHAT_CLI_MSG_FROM='[YOUR NAME]';"."\n";
			$help[] = "\t"."$ export HIPCHAT_CLI_MSG_COLOR='random';"."\n";
			$help[] = "\t"."$ export HIPCHAT_CLI_MSG_ROOM='[ROOM ID]';"."\n";
			$help[] = "\t"."$ export HIPCHAT_CLI_MSG_FORMAT='markdown';"."\n\n";

			$help[] = 'CLEANER EXAMPLES (ASSUMING YOU CONFIGURE ALL ENVIRONMENT VARIABLES):'."\n\n";

			$help[] = "\t"."$ hipchat 'Hello world!'"."\n";
			$help[] = "\t"."$ echo 'Hello World!' | hipchat"."\n";
			$help[] = "\t"."$ cat file.txt | hipchat"."\n";
			$help[] = "\t"."$ cat file.md | hipchat"."\n";
			$help[] = "\t"."$ cat file.html | hipchat"."\n\n";

			$help[] = 'ALL POSSIBLE OPTIONS:'."\n\n";

			$help[] = "\t"."-t, --token=''"."\n\n";
			$help[] = "\t\t".'HipChat API Token with the ability to send notifications. If missing, the environment variable `$HIPCHAT_CLI_TOKEN` is used as a default value.'."\n\n";

			$help[] = "\t"."-f, --from=''"."\n\n";
			$help[] = "\t\t".'The name your message will appear to come from. If missing, the environment variable `$HIPCHAT_CLI_MSG_FROM` is used as a default value.'."\n\n";

			$help[] = "\t"."-c, --color='yellow|red|green|purple|gray|random' (default: `random`)"."\n\n";
			$help[] = "\t\t".'The background color for your message. If missing, the environment variable `$HIPCHAT_CLI_MSG_COLOR` is used (if available); else a default value of `random`.'."\n\n";

			$help[] = "\t"."-r, --room=''"."\n\n";
			$help[] = "\t\t".'The numeric room ID where your message should be sent. If missing, the environment variable `$HIPCHAT_CLI_MSG_ROOM` is used as a default value.'."\n\n";

			$help[] = "\t"."-F, --format='text|markdown|html' (default: `markdown`)"."\n\n";
			$help[] = "\t\t".'The format of your message. If missing, the environment variable `$HIPCHAT_CLI_MSG_FORMAT` is used (if available); else a default value of `markdown`. This tool uses the Markdown Extra flavor. For further details, see: <http://michelf.ca/projects/php-markdown/extra/>. GFM-style code blocks are also supported with three or more backticks ```.'."\n\n";
			$help[] = "\t\t".'REGARDING MARKDOWN: Markdown is currently unsupported by the HipChat API. However, this CLI tool can easily overcome this limitation by processing Markdown locally; before ultimately sending your message through HipChat as basic `html`. While this works well, there are a couple of limitations to be aware of please.'."\n\n";
			$help[] = "\t\t\t".'1. If your message contains a `/slash` command (i.e. it starts with a slash `/command`) it is never treated as Markdown, even if you specify --format="markdown".'."\n\n";
			$help[] = "\t\t\t".'2. If your message contains an @mention it is never treated as Markdown, even if you specify --format="markdown". This is due to a limitation in the HipChat API. @mentions work only in `text` messages. If you type a message with an @mention, a format of `text` is enforced no matter what you specify.'."\n\n";
			$help[] = "\t\t".'NOTE ALSO: If you specify `markdown` or `html` but your message contains no Markdown syntax or HTML code (even after Markdown processing is completed locally); a value of `text` is enforced in this scenario; even if you specify --format="markdown|html". In short, there is no reason to send the message as HTML if it contains no HTML code.'."\n\n";

			$help[] = "\t"."-m, --message='' (or the last argument; or STDIN)"."\n\n";
			$help[] = "\t\t".'The content of your chat message.'."\n\n";

			$help[] = "\t"."-n, --notify (a simple flag only, off by default)"."\n\n";
			$help[] = "\t\t".'Pass this argument if you would like your message to trigger a notification for people in the room (change the tab color, play a sound, etc). Each recipient\'s notification preferences are taken into account.'."\n\n";

			$help[] = "\t"."-v, --version (a simple flag only)"."\n\n";
			$help[] = "\t\t".'Pass this argument if you would like to output the current version of this tool.'."\n\n";

			$help[] = "\t"."-h, --help (a simple flag only)"."\n\n";
			$help[] = "\t\t".'Pass this argument if you would like to see this help documentation in the future.'."\n\n";

			foreach($help as &$_block) // Wrap and preserve indentation.
				{
					$_block = preg_replace("/\t/", '   ', $_block); // Tab width = 3 spaces.
					$_block = wordwrap($_block); // Wrap at `75` chars; default value.

					if(preg_match('/^(?P<indentation>\s+)/', $_block, $_))
						$_block = preg_replace('/^/m', $_['indentation'], ltrim($_block));
				}
			unset($_block, $_); // Housekeeping.

			$this->output(implode('', $help), 0);
		}

	public function output($string, $exit_status = NULL)
		{
			echo $string."\n"; // Each output as a new line.

			if(isset($exit_status)) exit($exit_status);
		}

	public function curl($url, $body = '', $max_con_secs = 20, $max_stream_secs = 20, $headers = array(), $cookie_file = '', $fail_on_error = TRUE)
		{
			$curl_possible          = extension_loaded('curl');
			$curl_version           = ($curl_possible) ? curl_version() : array();
			$curl_over_ssl_possible = ($curl_possible && $curl_version['features'] & CURL_VERSION_SSL);

			if(!$curl_possible) // Do we have the cURL library available?
				$this->output('Missing cURL dependency.', 99);

			if(!$curl_over_ssl_possible) // It MUST support SSL too.
				$this->output('Missing cURL dependency; not compiled with OpenSSL support.', 98);

			$custom_request_method = (string)'';
			$url                   = (string)$url;
			$max_con_secs          = (integer)$max_con_secs;
			$max_stream_secs       = (integer)$max_stream_secs;
			$headers               = (array)$headers;

			$custom_request_regex = // e.g.`PUT::http://www.example.com/`
				'/^(?P<custom_request_method>(?:GET|POST|PUT|DELETE))\:{2}(?P<url>.+)/i';
			if(preg_match($custom_request_regex, $url, $_url_parts))
				{
					$url                   = $_url_parts['url']; // URL after `::`.
					$custom_request_method = strtoupper($_url_parts['custom_request_method']);
				}
			unset($_url_parts); // Housekeeping.

			if(is_array($body))
				$body = http_build_query($body, '', '&');
			else $body = (string)$body;

			if(!$url) return ''; // Nothing to do here.

			$can_follow = (!ini_get('safe_mode') && !ini_get('open_basedir'));

			$curl_opts = array(
				CURLOPT_URL            => $url,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_CONNECTTIMEOUT => $max_con_secs,
				CURLOPT_TIMEOUT        => $max_stream_secs,

				CURLOPT_RETURNTRANSFER => TRUE,
				CURLOPT_HEADER         => FALSE,

				CURLOPT_FOLLOWLOCATION => $can_follow,
				CURLOPT_MAXREDIRS      => $can_follow ? 5 : 0,

				CURLOPT_ENCODING       => '',
				CURLOPT_VERBOSE        => FALSE,
				CURLOPT_FAILONERROR    => $fail_on_error,
				CURLOPT_SSL_VERIFYPEER => FALSE
			);
			if($body) // Has a request body that we need to send?
				{
					if($custom_request_method) // A custom request method is given?
						$curl_opts += array(CURLOPT_CUSTOMREQUEST => $custom_request_method, CURLOPT_POSTFIELDS => $body);
					else $curl_opts += array(CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $body);
				}
			else if($custom_request_method) $curl_opts += array(CURLOPT_CUSTOMREQUEST => $custom_request_method);

			if($cookie_file) // Support cookies? e.g. we have a cookie jar available?
				$curl_opts += array(CURLOPT_COOKIEJAR => $cookie_file, CURLOPT_COOKIEFILE => $cookie_file);

			$curl = curl_init();
			curl_setopt_array($curl, $curl_opts);
			$output    = trim((string)curl_exec($curl));
			$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			return $output;
		}
}

