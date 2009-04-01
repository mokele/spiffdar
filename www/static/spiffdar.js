var auth_details = {
    name: "Spiffdar | Playlists for Playdar",
    website: "http://spiffdar.org/",
    receiverurl: "http://spiffdar.org/playdarauth.html",
};
var playdar = new Playdar(auth_details);
soundManager.url = '/static/deps/soundmanager2_flash9.swf';
soundManager.flashVersion = 9;
soundManager.onload = function() {
    playdar.soundmanager = soundManager;
    playdar.init();
}
var Spiffdar = Class.create({
    tracks: $H({}),
    loaded: false,
    delayed_loading: [],
    auth: false,
    delayed_auth: [],
    playing_sid: null,
    playing_qid: null,
    initialize: function(playdar) {
        document.observe('dom:loaded', function() {
            $('playdar_stat').show();
            this.list = $('list');
            this.addform = $('add');
            this.savebutton = $('save');
            this.playdar = playdar;
            this.addform.observe('submit', this.add_callback.bind(this));
            this.savebutton.observe('click', this.save_callback.bind(this));
            this.playdar.register_results_handler(
                this.results_handler.bind(this)
            );
            this.playdar.register_handler('stat', function(detected) {
                var text;
                if (detected) {
                    text = "<b style='color:green;'>Playdar ready</b>";
                } else {
                    text = "<b style='color:red;'>Playdar unavailable</b><br/>You need Playdar, the music content resolver, installed and running. See <a href=\"http://www.playdar.org/\">www.playdar.org</a>.";
                }
                $('playdar_stat').innerHTML = text;
                this.loaded = true;
                this.delayed_loading.each(function(func) {
                    func();
                });
                this.delayed_loading = [];
            }.bind(this));
            this.playdar.register_handler('auth', function() {
                this.auth = true;
                this.delayed_auth.each(function(func) {
                    func();
                });
                this.delayed_auth = [];
            }.bind(this));
        }.bind(this));
    },
    results_handler: function(response, final_answer) {
        this.tracks.get(response.qid).results_handler(response, final_answer);
    },
    setTitle: function(title) {
    },
    setAnnotation: function(annotation) {
    },
    add_callback: function(event) {
        event.stop();
        var artist = $('artist').value;
        var track = $('track').value;
        $('artist').value = '';
        $('track').value = '';
        $('artist').focus();
        this.add_track(artist, track);
    },
    save_callback: function(event) {
        event.stop();
        var serialized = this.serialize();
        if(!serialized) return;
        new Ajax.Request('/save.php', {
            method: 'post',
            parameters: {
                spiff: Object.toJSON(serialized)
            },
            onComplete: function(transport) {
                window.location.href = transport.responseText;
            }
        });
    },
    serialize: function() {
        var title = prompt('Give a name to your new playlist', '');
        if(!title) return;
        var serialized = {
            title: title,
            trackList: []
        };
        this.list.select('li').each(function(li) {
            if(li.id=="listitem_template") return;
            var item = {
                track: li.down('.track').innerHTML.unescapeHTML(),
                creator: li.down('.artist').innerHTML.unescapeHTML()
            };
            serialized.trackList.push(item);
        });
        return serialized;
    },
    delay_loaded: function(func) {
        if(this.loaded) {
            func();
        } else {
            this.delayed_loading.push(func);
        }
    },
    delay_auth: function(func) {
        if(this.auth) {
            func();
        } else {
            this.delayed_auth.push(func);
        }
    },
    get_track: function(qid) {
        if(qid.indexOf('qid_')==0) {
            //also allow the id of the element
            //as well as just the qid itself
            qid = qid.substr(4);
        }
        return this.tracks.get(qid);
    },
    add_track: function(artist, track) {
        this.delay_loaded(function() {
            if($('emptylist')) {
                $('emptylist').hide();
            }
            if($('loading')) {
                $('loading').hide();
            }
            var qid = Playdar.generate_uuid();
            var row = this.new_row(qid, artist, track);
            var spiffTrack = new SpiffdarTrack(qid, row, this);
            this.tracks.set(qid, spiffTrack);
            this.delay_auth(function() {
                this.resolve(spiffTrack);
            }.bind(this));
            return spiffTrack;
        }.bind(this));
    },
    /**
     * resolved based on a fifo queue
     */
    resolution_queue: $A([]),
    processing_resolution_queue: 0,
    resolution_queue_size: 100,
    resolve: function(spiffTrack) {
        this.resolution_queue.push(spiffTrack);
        this.ping_resolution_queue();
    },
    ping_resolution_queue: function() {
        if(this.processing_resolution_queue < this.resolution_queue_size) {
            var upto = this.resolution_queue_size
                      -this.processing_resolution_queue;
            for(var i=1; i<=upto; i++) {
                var spiffTrack = this.resolution_queue.shift();
                if(!spiffTrack) {
                    break;
                }
                this.processing_resolution_queue++;
                spiffTrack.resolve(this.track_resolution_done.bind(this));
            }
        }
    },
    track_resolution_done: function() {
        this.processing_resolution_queue--;
        this.ping_resolution_queue();
    },
    new_row: function(qid, artist, track) {
        var row = $('listitem_template').cloneNode(true);
        /*
        var tbody = this.list.down('tbody');
        if(!tbody) {
            tbody = this.list;
        }
        tbody.appendChild(row);
        */
        this.list.insert({bottom: row});
        row.id = 'qid_' + qid;
        row.down('.artist').update(artist);
        row.down('.track').update(track);
        if(this.tracks.size() % 2 == 0) {
            row.addClassName('odd');
        }
        return row;
    },
    play: function(track) {
        if(!track) {
            track = this.tracks.get(this.tracks.keys().pop());
            if(!track) return
        }
        if(!track.isResolved) {
            var nextElement = track.element.next();
            if(!nextElement) return;//end of list
            var next = this.get_track(nextElement.id);
            this.play(next);
            return;
        }
        if(track.qid != this.playing_qid && this.playing_qid) {
            //note change to sid here
            this.playdar.play_stream(this.playing_sid);
        }
        
        if(this.playing_qid && this.playing_qid==track.qid || !track.isResolved) {
            this.playing_sid = null;
            this.playing_qid = null;
        } else {
            this.playing_sid = track.sid;
            this.playing_qid = track.qid;
        }
        this.playdar.play_stream(track.sid);//does play and pause atm
    },
    play_next: function(track) {
        var keys = this.tracks.keys();
        var index = keys.indexOf(track.qid) + 1;
        if(keys[index]) {
            var next = this.tracks.get(keys[index]);
            this.play(next);
        }
    }
});
var SpiffdarTrack = Class.create({
    playing: false,
    isResolved: false,
    resolutions: $A([]),
    resolved_callback: null,
    source_count: 0,
    initialize: function(qid, element, spiffdar) {
        this.element = element;
        this.qid = qid;
        this.artist = this.element.down('.artist').innerHTML;
        this.track = this.element.down('.track').innerHTML;
        this.spiffdar = spiffdar;
        //and for convenience
        this.playdar = spiffdar.playdar;
    },
    resolve: function(callback) {
        this.resolved_callback = callback;
        this.spiffdar.playdar.resolve(this.artist, '', this.track, this.qid);
    },
    results_handler: function(response, final_answer) {
        if(final_answer) {
            var first = response.results.first();
            if(first) {
                this.resolved(first);
            } else {
                this.not_resolved();
            }
        }
        this.add_resolutions(response.results);
    },
    add_resolutions: function(results) {
        var diff = $A(results).select(function(item) {
            return !this.resolutions.find(function(item2) {
                return item.sid==item2.sid;
            });
        }.bind(this));
        this.resolutions = this.resolutions.concat(diff);
        if(diff.size() > 0) {
            this.increment_source_count(diff.size());
        }
    },
    increment_source_count: function(count) {
        var orig = this.source_count;
        this.source_count += count;
        if(this.source_count > 1) {
            var sc = this.element.down('.sourceCount');
            sc.update('('+this.source_count+')');
            if(orig < 2) {
                sc.observe('click', this.callback_source_count.bind(this));
            }
        }
    },
    callback_source_count: function(event) {
        event.stop();
        //todo: don't regenerate this every time?
        this.hide_resolutions();
        this.resolution_options = new Element('ul', { 'class': 'resolutions' });
        this.resolutions.each(function(result) {
            var track = new Element('span', { 'class': 'track' }).update(result.track);
            var artist = new Element('span', { 'class': 'artist' }).update(result.artist);
            var sourceInfo = new Element('span').update(
                Playdar.mmss(result.duration)
                + ', Source: ' + result['source']
                + ', ' + result.bitrate + 'kbps');
            
            var a = new Element('a', { 'id': 'sid_' + result.sid });
            a.appendChild(track);
            a.appendChild(artist);
            a.appendChild(sourceInfo);
            //todo: refactor into a source class
            a.observe('click', this.change_source.bind(this));
            var li = new Element('li', { 'class': result.sid==this.sid ? 'selected':'' });
            li.appendChild(a)
            this.resolution_options.appendChild(li);
        }.bind(this));
        this.element.down('.resolvedInfo').insert({bottom: this.resolution_options});
        this.last_options_callback = this.hide_resolutions.bind(this);
        $(document.body).observe('click', this.last_options_callback);
        
    },
    change_source: function(event) {
        event.stop();
        var sid = event.findElement('a').id.substr(4);
        var result = this.resolutions.find(function(r) {
            return r.sid == sid;
        });
        var playing = this.playing;
        //playdar does some other checks we don't want to kill
        if(playing) {
            this.spiffdar.play(this);//pause
        }
        //this kills playdar atm due to it's currently playing checks
        //this.sound.destruct();
        //this.register_stream(result);
        this.resolved(result, true);
        this.hide_resolutions();
        if(playing) {
            this.spiffdar.play(this);
        }
    },
    hide_resolutions: function() {
        if(this.resolution_options) {
            this.resolution_options.remove();
            this.resolution_options = null;
        }
        if(this.last_options_callback) {
            $(document.body).stopObserving('click', this.last_options_callback);
            this.last_options_callback = null;
        }
        this.element.removeClassName('hover');
    },
    not_resolved: function() {
        if(this.resolved_callback) {
            this.resolved_callback();
        }
    },
    resolved: function(result, nobind) {
        //when we resolve other times against other results
        //we don't want to rebind these events, todo: move this 
        //somewhere else?
        if(!nobind) {
            this.element.observe('click', this.click_callback.bind(this));
            this.element.observe('mouseover', this.mouseover_callback.bind(this));
            this.element.observe('mouseout', this.mouseout_callback.bind(this));
        }
        this.isResolved = true;
        this.element.addClassName('resolved');
        this.element.down('.time').update(Playdar.mmss(result.duration));
        this.element.down('.artist').update(result.artist);
        this.element.down('.track').update(result.track);
        this.element.down('.source').update(result['source']);
        //todo: move to the point of playback instead of resolutions
        this.register_stream(result);
        if(this.resolved_callback) {
            this.resolved_callback();
        }
    },
    register_stream: function(result) {
        this.sid = result.sid;
        this.sound = this.playdar.register_stream(result, {
            onfinish: this.notification_finished.bind(this),
            onpause: this.notification_paused.bind(this),
            onplay: this.notification_played.bind(this),
            onresume: this.notification_played.bind(this),
            onstreamfailure: this.notification_streamfailure.bind(this)
        });
    },
    click_callback: function(event) {
        event.stop();
        this.spiffdar.play(this);
        this.hide_resolutions();
    },
    mouseover_callback: function(event) {
        event.stop();
        this.element.addClassName('hover');
    },
    mouseout_callback: function(event) {
        event.stop();
        this.element.removeClassName('hover');
    },
    notification_paused: function() {
        this.element.removeClassName('playing');
        this.element.addClassName('paused');
        this.playing = false;
    },
    notification_played: function() {
        this.element.removeClassName('paused');
        this.element.addClassName('playing');
        this.element.removeClassName('streamfailure');
        this.playing = true;
    },
    notification_streamfailure: function() {
        if(!this.playing) return;//doesn't matter anymore!
        var nextResolution = null;
        var currentResolution = null;
        this.resolutions.each(function(r) {
            if(r.sid == this.sid) {
                currentResolution = r;
            } else if(currentResolution && !nextResolution) {
                nextResolution = r;
            }
        }.bind(this));
        if(nextResolution) {
            //next resolution
            this.resolved(nextResolution, true);
            this.spiffdar.play(this);
        } else {
            this.element.addClassName('streamfailure');
            //the whileplaying event next gets hit again
            //by soundmanager, so we need to remake it all :(
            this.sound.destruct();
            this.register_stream(currentResolution);
            this.notification_finished();
        }
    },
    notification_finished: function() {
        this.notification_paused();//todo: stopped
        //this.spiffdar.play_next(this);
        //todo: move into Spiffdar
        this.spiffdar.playing_qid = null;
        this.spiffdar.playing_sid = null;
        
        var nextElement = this.element.next();
        if(!nextElement) return;//end of list
        var next = this.spiffdar.get_track(nextElement.id);
        this.playing = false;
        this.spiffdar.play(next);
    }
});

    
