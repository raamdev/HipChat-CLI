## HipChat CLI (Command Line Interface)

Command-line tools for HipChat. Currently one. This command-line tool sends messages to a HipChat room. This tool currently supports all possible options that are made available in version 1 of the HipChat API. For further details, please see: <https://www.hipchat.com/docs/api/method/rooms/message>. *In addition, this tool also adds support for Markdown (optional).*

#### Requirements

- PHP v5.3+
- cURL extension for PHP.
- cURL needs to support SSL connections.

#### Installation Instructions

###### Ubuntu via WebSharks PPA

```
$ sudo add-apt-repository ppa:websharks/ppa --yes && sudo apt-get update --yes;
$ sudo apt-get install hipchat-cli --yes;
```

###### All Other Systems (Manual Install)

- Download the ZIP from GitHub and extract the directory locally.
- Make the `HipChat-CLI/message.php` file executable; i.e. `chmod +x message.php`
- Finally, create a symlink in your `~/bin` directory so that `hipchat-msg` (or whatever command name you prefer) will be in your `$PATH`; e.g. `ln --symbolic /path/to/HipChat-CLI/message.php ~/bin/hipchat-msg`

#### Usage

```
$ hipchat-msg --token='akaxlsdow234er443ssdlskdoeeesdfls9434' --from='John' --room='555555' --message='Hello world!';
```

##### **TIP:** environment variables save time...

You can read the documentation below regarding environment variables that are supported by this tool. With environment variables you can shorten command-line usage to just: `$ hipchat-msg [message]`. For instance, you could drop all the other arguments if you define the following environment variables. The following lines might go inside your `~/.profile`

```
export HIPCHAT_CLI_TOKEN='[YOUR HIPCHAT API TOKEN]';
export HIPCHAT_CLI_MSG_FROM='[YOUR NAME]';
export HIPCHAT_CLI_MSG_COLOR='random';
export HIPCHAT_CLI_MSG_ROOM='[ROOM ID]';
export HIPCHAT_CLI_MSG_FORMAT='markdown';
```

#### Cleaner Examples (Assuming You Configured All Environment Variables)

```
$ hipchat-msg 'Hello world!'
$ echo 'Hello World!' | hipchat-msg
$ cat file.txt | hipchat-msg
$ cat file.md | hipchat-msg
$ cat file.html | hipchat-msg
```

## Full Documentation w/ All Possible Arguments
Or from the command-line type: `$ hipchat-msg --help`

	~~~~~~~~~~ HipChat CLI Help/Documentation for: hipchat-msg ~~~~~~~~~~

	   This command-line tool sends messages to a HipChat room. This tool
	   currently supports all possible options that are made available in version
	   1 of the HipChat API. For further details, please see:
	   <https://www.hipchat.com/docs/api/method/rooms/message>

	USAGE:

	   $ hipchat-msg [options] [message]

	EXAMPLE (MINIMUM REQUIRED ARGUMENTS):

	   $ hipchat-msg --token='akaxlsdow234er443ssdlskdoeeesdfls9434'
	   --from='John' --room='555555' --message='Hello world!'

	EXAMPLE (MESSAGE AS LAST ARGUMENT INSTEAD OF PASSING --message OPTION):

	   $ hipchat-msg --token='akaxlsdow234er443ssdlskdoeeesdfls9434'
	   --from='John' --room='555555' 'Hello world!'

	ENVIRONMENT VARIABLES (OPTIONAL):

	   $ export HIPCHAT_CLI_TOKEN='[YOUR HIPCHAT API TOKEN]';
	   $ export HIPCHAT_CLI_MSG_FROM='[YOUR NAME]';
	   $ export HIPCHAT_CLI_MSG_COLOR='random';
	   $ export HIPCHAT_CLI_MSG_ROOM='[ROOM ID]';
	   $ export HIPCHAT_CLI_MSG_FORMAT='markdown';

	CLEANER EXAMPLES (ASSUMING YOU CONFIGURE ALL ENVIRONMENT VARIABLES):

	   $ hipchat-msg 'Hello world!'
	   $ echo 'Hello World!' | hipchat-msg
	   $ cat file.txt | hipchat-msg
	   $ cat file.md | hipchat-msg
	   $ cat file.html | hipchat-msg

	ALL POSSIBLE OPTIONS:

	   -t, --token=''

	      HipChat API Token with the ability to send notifications. If missing,
	      the environment variable `$HIPCHAT_CLI_TOKEN` is used as a default value.

	   -f, --from=''

	      The name your message will appear to come from. If missing, the
	      environment variable `$HIPCHAT_CLI_MSG_FROM` is used as a default value.

	   -c, --color='yellow|red|green|purple|gray|random' (default: `random`)

	      The background color for your message. If missing, the environment
	      variable `$HIPCHAT_CLI_MSG_COLOR` is used (if available); else a default
	      value of `random`.

	   -r, --room=''

	      The numeric room ID where your message should be sent. If missing,
	      the environment variable `$HIPCHAT_CLI_MSG_ROOM` is used as a default
	      value.

	   -F, --format='text|markdown|html' (default: `markdown`)

	      The format of your message. If missing, the environment variable
	      `$HIPCHAT_CLI_MSG_FORMAT` is used (if available); else a default value of
	      `markdown`. This tool uses the Markdown Extra flavor. For further details,
	      see: <http://michelf.ca/projects/php-markdown/extra/>. GFM-style code
	      blocks are also supported with three or more backticks ```.

	      REGARDING MARKDOWN: Markdown is currently unsupported by the HipChat
	      API. However, this CLI tool can easily overcome this limitation by
	      processing Markdown locally; before ultimately sending your message through
	      HipChat as basic `html`. While this works well, there are a couple of
	      limitations to be aware of please.

	         1. If your message contains a `/slash` command (i.e. it starts
	         with a slash `/command`) it is never treated as Markdown, even if you
	         specify --format="markdown".

	         2. If your message contains an @mention it is never treated as
	         Markdown, even if you specify --format="markdown". This is due to a
	         limitation in the HipChat API. @mentions work only in `text` messages. If
	         you type a message with an @mention, a format of `text` is enforced no
	         matter what you specify.

	      NOTE ALSO: If you specify `markdown` or `html` but your message
	      contains no Markdown syntax or HTML code (even after Markdown processing is
	      completed locally); a value of `text` is enforced in this scenario; even if
	      you specify --format="markdown|html". In short, there is no reason to send
	      the message as HTML if it contains no HTML code.

	   -m, --message='' (or the last argument; or STDIN)

	      The content of your chat message.

	   -n, --notify (a simple flag only, off by default)

	      Pass this argument if you would like your message to trigger a
	      notification for people in the room (change the tab color, play a sound,
	      etc). Each recipient's notification preferences are taken into account.

	   -v, --version (a simple flag only)

	      Pass this argument if you would like to output the current version of
	      this tool.

	   -h, --help (a simple flag only)

	      Pass this argument if you would like to see this help documentation
	      in the future.