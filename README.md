# Thread Redirect plugin for MyBB 1.8

This plugin allows threads to redirect to a URL with optional custom text.

## Installation

1. Upload all files inside the "UPLOAD" folder into your MyBB root directory, keeping the file structure.
2. Login to your Admin CP and head to Configuration -> Plugins.
3. Click on "Install & Activate" next to the "Thread Redirect" plugin.

## Usage

Firstly, you will need to set up who can create a redirecting thread.

1. Login to your admin CP.
2. Go to Configuration -> Settings
3. Go down to Plugin Settings -> Thread Redirect
4. Select the "Allowed Groups" to define who can create a redirecting thread.

To create a thread which redirects to a URL:
1. Create a new topic
2. Enter an URL into the "Redirect URL" field.
3. Create the topic WITHOUT a message

Or

3. Enter a message and create the topic

If you enter a message, when the user clicks onto the thread, they will see the message on the 'redirect' screen before being redirected. If you don't enter a message, the user will be redirected straight to the URL.

## Credits

- MyBB - [https://mybb.com](https://mybb.com)
- Jamie Sage - [https://www.jamiesage.co.uk](https://www.jamiesage.co.uk)

## License

[MIT License](https://github.com/jamiesage123/Thread-Redirect/blob/master/LICENSE)
