var playdar = new Playdar({
    stat_complete: function (detected) {
        var text;
        if (detected) {
            text = "<b style='color:green;'>Playdar ready</b>";
        } else {
            text = "<b style='color:red;'>Playdar unavailable</b><br/>You need Playdar, the music content resolver, installed and running. See <a href=\"http://www.playdar.org/\">www.playdar.org</a>.";
        }
        $('playdar_stat').innerHTML = text;
    },
    soundmanager_ready: function () {
        parseHash();

    }
});
soundManager.url = '/static/deps/soundmanager2_flash9.swf';
soundManager.flashVersion = 9;
soundManager.onload = function() {
    playdar.init();
    playdar.soundmanager = soundManager;
}
//playdar.register_soundmanager(soundManager);

var Spiffdar = Class.create({
    tracks: $H({}),
    initialize: function(playdar) {
        this.list = $('list');
        this.addform = $('add');
        this.playdar = playdar;
        this.addform.observe('submit', this.add_callback.bind(this));
        this.playdar.register_results_handler(
            this.results_handler.bind(this)
        );
    },
    results_handler: function(response, final_answer) {
        this.tracks.get(response.qid).results_handler(response, final_answer);
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
    add_track: function(artist, track) {
        var qid = Playdar.generate_uuid();
        var row = this.new_row(qid, artist, track);
        var track = new SpiffdarTrack(qid, row, this);
        this.tracks.set(qid, track);
        track.resolve();
        return track;
    },
    new_row: function(qid, artist, track) {
        var row = $('listitem_template').cloneNode(true);
        var tbody = this.list.down('tbody');
        if(!tbody) {
            tbody = this.list;
        }
        tbody.appendChild(row);
        row.id = 'qid_' + qid;
        row.down('td.position').update(this.tracks.size()+1);
        row.down('td.artist').update(artist);
        row.down('td.track').update(track);
        if(this.tracks.size() % 2 == 0) {
            row.addClassName('odd');
        }
        return row;
    },
    playing_sid: null,
    playing_qid: null,
    play: function(track) {
        if(!track.isResolved) {
            var keys = this.tracks.keys();
            var index = keys.indexOf(track.qid) + 1;
            if(keys[index]) {
                var next = this.tracks.get(keys[index]);
                this.play(next);
                return;
            }
        }
        
        if(track.sid != this.playing_sid && this.playing_sid) {
            this.playdar.play_stream(this.playing_sid);
        }
        
        this.playdar.play_stream(track.sid);
        if(this.playing_sid==track.sid) {
            track.notification_paused();
            this.playing_sid = null;
            this.playing_qid = null;
        } else {
            if(this.playing_sid) {
                this.tracks.get(this.playing_qid).notification_paused();
            }
            if(track.isResolved) {
                track.notification_played();
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
    initialize: function(qid, tr, spiffdar) {
        this.tr = tr;
        this.qid = qid;
        this.artist = this.tr.down('td.artist').innerHTML;
        this.track = this.tr.down('td.track').innerHTML;
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
        this.tr.addClassName('resolved');
        this.tr.observe('click', this.click_callback.bind(this));
        this.tr.down('.time').update(Playdar.mmss(result.duration));
        this.tr.down('.artist').update(result.artist);
        this.tr.down('.track').update(result.track);
        this.sid = result.sid;
        var sound = this.playdar.register_stream(result, {
            onfinish: function () {
                this.notification_paused();//todo: stopped
                //this.spiffdar.play_next(this);
                //todo: move into Spiffdar
                this.spiffdar.playing_qid = null;
                this.spiffdar.playing_sid = null;
                var keys = this.spiffdar.tracks.keys();
                var index = keys.indexOf(this.qid) + 1;
                if(keys[index]) {
                    var next = this.spiffdar.tracks.get(keys[index]);
                    this.spiffdar.play(next);
                }
            }.bind(this)
        });
    },
    click_callback: function(event) {
        event.stop();
        this.spiffdar.play(this);
    },
    notification_paused: function() {
        this.tr.removeClassName('playing');
        this.tr.addClassName('paused');
    },
    notification_played: function() {
        this.tr.removeClassName('paused');
        this.tr.addClassName('playing');
    }
});


document.observe('dom:loaded', function() {
    spiffdar = new Spiffdar(playdar);
});