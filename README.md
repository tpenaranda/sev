# Streamer Event Viewer
## Code assignment for Streamlabs.

### Notes

> You are not expected to (cumulatively) spend more than *4* hours on this task.

Well... call me dumb but I spent about 3 days on this, and, I didn't wrote a line of code before the second day.
Maybe because I'm new to gaming and Twitch, but from my perspective there is no chance to get an MVP in 4hs here.

> Ability to navigate third-party interfaces (libraries/apis) where the documentation/spec might not be very detailed

Not detailed and a pain to use! God! Can't believe they call 'new api' and 'v5 api'. Who the hell tags 'new' an API?.
I mean, "New" is going to became "Old", soon or later... I'm wondering if twitch api git tags/branches are named 'new' haha.
Versions please. Helix, old, new, kraken, v5... come on. Be consistent. As a plus... nothing standarized, non descriptive
responses, lack of features and so on hehe.

> Link to live demo (heroku tends to be pretty easy/free and is used by most people)

Since I used Laravel/VueJS, the app is being deployed using Forge (official Laravel product). [Live demo](https://sev.xenir.com.ar)

> Link to git repo (Github is a famous choice) consisting your source and `README`

[Here](https://github.com/tpenaranda/sev)

> The first/home page lets a user login with Twitch and set their favorite Twitch streamer name.
> This initiates a backend event listener which listens to all events for given streamer.

This is handled by setWebhook@MainController, everytime a new streamer is set, a POST is made to 'twitch/listen', the BE
subscribes to Twitch stream-change and user-followed webhooks (synchronically, that's not good).
Finally webhooks are received at our BE and we store the event on Redis (list with json data, not the best way to store but it's easy and fast).
Small hack: to the webhook (hub.callback) URL I'm appending a user_id param... since for "stream offline" Twitch just sends an empty array of data,
there is no 'user_id' or 'stream_status' key.

> The second/streamer page shows an embedded livestream, chat and list of 10 most recent events for your favorite streamer.
> This page doesn’t poll the backend and rather leverages web sockets and relevant Twitch API.

Here... my assumption is 'Events' means webhooks or chat events. (Not the event tab on the Twitch stream dashboard, there is no
documentation on how to retrieve those events but I found this [package](https://www.npmjs.com/package/@lund-org/twitch-events)
and I noticed we could retrieve those events the same way Twitch FE does, using the same endpoint).
Livestream... easy, just an iframe, fixed size, no responsiveness (well, nothing is responsive on this FE).
Chat... easy, with tmi.js package, TMI supports listening of many events (plus the most obvious 'message' event).
I'm not handligs those events, I just writing "event" on the chat window.
And finally... 10 most recent events... that's (for me) what the webhooks incoming endpoint catches. So, I created an endpoint
to GET those events. On Redis we don't store "the event object", cheap approach there, we store timestamp and description.

> How would you deploy the above on AWS? (ideally a rough architecture diagram will help)

No clue, never deployed Laravel with AWS... but for Forge (or Heroku), well not big deal, create project, push to github,
sync with deploy, deploy script, DB, Redis... and voilà. Forge handles, ssl, load balancers, scalability, logs, unit testing,
pipelines and so on.

> Where do you see bottlenecks in your proposed architecture and how would you approach scaling this app starting from
> 100 reqs/day to 900MM reqs/day over 6 months?

Well... "sync" is a no-no. Redis can handle that, but sync calls to Twitch on the BE are gonna hurt.
And here, we're not checking if the 'favorite streamer' is being already "watched" by the BE. Because maybe we could have a
million users but all of them just want to watch the same streamers, probably a few (maybe thousands).
So... async and being "smart" when watching streamers.

Tate Peñaranda