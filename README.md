# sipgate-jabber-notify

A small PHP CLI script* that sends sends notifications about missed calls from your [sipgate](https://www.sipgate.de/basic/) number to your jabber account.
It makes use of the [Sipgate XML-RPC API](http://www.sipgate.de/img/sipgate_api_documentation.pdf).

The script must be run with a cronjob. It checks you sipgate account for missed calls and sends jabber messages.

It also looks up the originating number via [clicktel Open API](http://openapi.klicktel.de). You'll need a clicktel API key for this feature.

* PHP for CLI scripts? Of course there are quite more encouraging ways to build shell scripts... but hey, it works! :+1: :smile:

## Dependencies
[phpxmlrpc](https://gggeek.github.io/phpxmlrpc/)
[XMPPHP](https://code.google.com/p/xmpphp/)
