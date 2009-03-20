var playdar = new Playdar();
soundManager.url = '/static/deps/soundmanager2_flash9.swf';
soundManager.flashVersion = 9;
soundManager.onload = function() {
    playdar.init();
    playdar.soundmanager = soundManager;
}

var Spiffdar = Class.create({
    tracks: $H({}),
    loaded: false,
    delayed_loading: [],
    playing_sid: null,
    playing_qid: null,
    initialize: function(playdar) {
        document.observe('dom:loaded', function() {
            this.list = $('list');
            this.addform = $('add');
            this.playdar = playdar;
            this.addform.observe('submit', this.add_callback.bind(this));
            this.playdar.register_results_handler(
                this.results_handler.bind(this)
            );
            this.playdar.register_handler('stat_complete', function(detected) {
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
    delay_loaded: function(func) {
        if(this.loaded) {
            func();
        } else {
            this.delayed_loading.push(func);
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
            var qid = Playdar.generate_uuid();
            var row = this.new_row(qid, artist, track);
            var spiffTrack = new SpiffdarTrack(qid, row, this);
            this.tracks.set(qid, spiffTrack);
            spiffTrack.resolve();
            return spiffTrack;
        }.bind(this));
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
        row.down('.position').update(1);
        row.down('.artist').update(artist);
        row.down('.track').update(track);
        if(this.tracks.size() % 2 == 0) {
            row.addClassName('odd');
        }
        this.reindex();
        return row;
    },
    reindex: function() {
        var i = 1;
        this.list.select('li .position').each(function(pos) {
            if(pos.up('li').id!="listitem_template") {
                pos.update(i++);
            }
        });
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
        
        if(track.sid != this.playing_sid && this.playing_sid) {
            this.playdar.play_stream(this.playing_sid);
        }
        
        this.playdar.play_stream(track.sid);
        if(this.playing_sid==track.sid) {
            this.playing_sid = null;
            this.playing_qid = null;
        } else {
            if(track.isResolved) {
                this.playing_sid = track.sid;
                this.playing_qid = track.qid;
            } else {
                //terminating statement
                this.playing_sid = null;
                this.playing_qid = null;
            }
        }
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
    resolved: false,
    initialize: function(qid, element, spiffdar) {
        this.element = element;
        this.qid = qid;
        this.artist = this.element.down('.artist').innerHTML;
        this.track = this.element.down('.track').innerHTML;
        this.spiffdar = spiffdar;
        //and for convenience
        this.playdar = spiffdar.playdar;
    },
    resolve: function() {
        this.spiffdar.playdar.resolve(this.artist, '', this.track, this.qid);
    },
    results_handler: function(response, final_answer) {
        if(final_answer) {
            var first = response.results.first();
            if(first) {
                this.resolved(first);
            }
        }
    },
    resolved: function(result) {
        this.isResolved = true;
        this.element.addClassName('resolved');
        this.element.observe('click', this.click_callback.bind(this));
        this.element.observe('mouseover', this.mouseover_callback.bind(this));
        this.element.observe('mouseout', this.mouseout_callback.bind(this));
        this.element.down('.time').update(Playdar.mmss(result.duration));
        this.element.down('.artist').update(result.artist);
        this.element.down('.track').update(result.track);
        this.sid = result.sid;
        var sound = this.playdar.register_stream(result, {
            onfinish: function () {
                this.notification_paused();//todo: stopped
                //this.spiffdar.play_next(this);
                //todo: move into Spiffdar
                this.spiffdar.playing_qid = null;
                this.spiffdar.playing_sid = null;
                
                var nextElement = this.element.next();
                if(!nextElement) return;//end of list
                var next = this.spiffdar.get_track(nextElement.id);
                this.spiffdar.play(next);
            }.bind(this),
            onpause: this.notification_paused.bind(this),
            onplay: this.notification_played.bind(this),
            onresume: this.notification_played.bind(this)
        });
    },
    click_callback: function(event) {
        event.stop();
        this.spiffdar.play(this);
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
    },
    notification_played: function() {
        this.element.removeClassName('paused');
        this.element.addClassName('playing');
    }
});

spiffdar = new Spiffdar(playdar);
    