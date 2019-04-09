<template>
    <div class="container">
        <div class="row justify-content-center pt-4">
            <div class="card col-md-8 px-0">
                <div class="card-header">Streamer Event Viewer</div>
                <div class="card-body text-center">
                    <div v-if="accessToken" class="text-center">
                        <label for="favorite_streamer" class="pr-2">Favorite Streamer:</label>
                        <input type="text" class="text-center" v-model="streamer" name="favorite_streamer">
                        <button class="btn-primary ml-2 px-4" @click="setFavoriteStreamer" :disabled="request.inProgress || !streamer">{{ set_button_text }}</button>
                        <div class="pt-2">{{ request.message }}</div>
                    </div>
                    <button v-else class="btn-primary" @click="loginWithTwitch">
                        <svg class="tw-svg__asset tw-svg__asset--inherit tw-svg__asset--logoglitch" width="24px" height="24px" version="1.1" viewBox="0 0 30 30" x="0px" y="0px">
                            <path clip-rule="evenodd" d="M21,9h-2v6h2V9z M5.568,3L4,7v17h5v3h3.886L16,24h5l6-6V3H5.568z M25,16l-4,4h-6l-3,3v-3H8V5h17V16z M16,9h-2v6h2V9z" fill-rule="evenodd"></path>
                        </svg>
                        Login with Twitch
                    </button>
                </div>
            </div>
            <div v-show="livestream.show" class="card col-md-8 px-0 mt-3">
                <div class="card-header">Livestream</div>
                <div class="card-body text-center">
                    <div id="livestream"></div>
                </div>
            </div>
            <div v-if="accessToken && chat.enabled" class="card col-md-8 px-0 mt-3">
                <div class="card-header">
                    <span>Chat</span>
                    <span v-if="chat.connected">
                        {{ chat.client.getChannels().join(' - ') }}
                    </span>
                    <button v-else class="btn-primary ml-2 px-4" @click="chatConnect" :disabled="chat.connecting || chat.connected">
                        {{ connect_button_text }}
                    </button>
                </div>
                <div class="card-body" v-if="chat.connected">
                    <div v-for="message in chat.messages">
                        <b>{{ message.username   }}</b>: {{ message.message }}
                    </div>
                    <div class="text-center pt-2">
                        <input type="text" class="text-right" v-model="chat.message" name="chat_message" maxlength="64" size="64">
                        <button class="btn-primary" @click="sendChatMessage" :disabled="!chat.message">Send!</button>
                    </div>
                </div>
            </div>
            <div class="card col-md-8 px-0 mt-3" v-if="webhooks.length">
                <div class="card-header">
                    <span>Logged Events (Server side)</span>
                    <button class="btn-primary ml-2 px-4" @click="pullWebhookLogs">Refresh</button>
                </div>
                <div class="card-body">
                    WIP
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props: [
            'accessToken',
            'clientId',
            'favoriteStreamer'
        ],
        data () {
            return {
                streamer: this.favoriteStreamer,
                request: {
                    inProgress: false,
                    message: null,
                },
                chat: {
                    client: null,
                    connected: null,
                    connecting: null,
                    enabled: false,
                    message: '',
                    messages: []
                },
                livestream: {
                    show: false,
                    player: null
                },
                webhooks: []
            }
        },
        mounted () {
            this.instanceChatClient()
            this.pullWebhookLogs()
        },
        computed: {
            connect_button_text () {
                return this.chat.connecting ? 'Connecting...' : 'Connect'
            },
            set_button_text () {
                return this.request.inProgress ? 'Setting...' : 'Set'
            }
        },
        methods: {
            pullWebhookLogs () {
                axios.get('/twitch/webhooks').then((response) => {
                    this.webhooks = response.data.data
                }).catch((error) => {
                    this.webhooks = []
                })
            },
            instanceLiveStream (channel) {
                this.livestream.player = new Twitch.Player('livestream', {width: 640, height: 480, channel: channel})
            },
            setLiveStreamChannel () {
                if (!this.livestream.player) {
                    this.instanceLiveStream(this.streamer)
                } else {
                    this.livestream.player.setChannel(this.streamer)
                }

                this.livestream.show = true
            },
            instanceChatClient () {
                let opts = {
                    options: {
                        clientId: this.clientId,
                    },
                    connection: {
                        secure: true
                    },
                    identity: {
                        username: 'You',
                        password: `oauth:${this.accessToken}`
                    },
                    channels: [ this.streamer ]
                }

                this.chat.client = new tmi.client(opts);

                this.chat.client.on('connecting', () => {
                    this.chat.connecting = true
                })

                this.chat.client.on('connected', () => {
                    this.chat.connected = true
                    this.chat.connecting = false
                })

                this.chat.client.on('disconnected', () => {
                    this.chat.connected = false
                    this.chat.connecting = false
                    this.chat.messages = []
                })

                this.chat.client.on('message', (channel, userstate, message, self) => {
                    if (self) {
                        return false
                    }

                    switch(userstate["message-type"]) {
                        case "chat":
                            this.appendChatMessage(userstate['username'], message)
                            break;
                        default:
                            console.log(channel, userstate, message)
                            break;
                    }
                });
            },
            appendChatMessage (username, message = '') {
                this.chat.messages.push({
                    username: username,
                    message: message
                })

                this.chat.messages = this.chat.messages.slice(-10)
            },
            chatConnect () {
                this.instanceChatClient()
                this.chat.client.connect()
            },
            sendChatMessage () {
                let message = this.chat.message.valueOf()

                this.chat.client.say(this.streamer, message).then(() => {
                    this.chat.messages.push({
                        username: 'You',
                        message: message
                    })
                })
                this.chat.message = ''
            },
            loginWithTwitch () {
                window.location.href = '/login/twitch'
            },
            setFavoriteStreamer () {
                this.request.inProgress = true
                axios.post('/twitch/listen', {streamer: this.streamer}).then((response) => {
                    this.request.message = 'BE Listener created!'
                    if (this.chat.connected) {
                        this.chat.client.disconnect()
                    }
                    this.messages = []
                    this.chat.enabled = true
                    this.setLiveStreamChannel()
                }).catch((error) => {
                    this.request.message = error.response.data.error || 'Ups, something went wrong!'
                    this.streamer = null
                }).finally(() => {
                    this.request.inProgress = false
                    setTimeout(() => {
                        this.request.message = null
                    }, 5000)
                })
            }
        }
    }
</script>
