class Session {
    static singleton=false;
    static session=null;
    static getSession(settings) {
        if(!Session.session) {
            Session.singleton = true;
            Session.session = new Session(settings);
            Session.singleton = false;
        }
        return Session.session
    }

    id='laminas_expireSessionDialog';
    constructor(settings) {
        if(!Session.singleton) {
            throw new Error('Cannot initalize Session directly. Call Session.getSession() instead.');
        }
        Session.session = this;
        if(!settings.expireAt) {
            console.error('settings.expireAt is required');
        }
        this.settings = settings;
        if(settings.id && document.getElementById(settings.id)) {
            this.id = settings.id;
        }

        this.i18nText = {
            buttonContinue: "Continue",
            buttonEnd: "End",
            buttonSignin: "Sign in",
            timeoutBegin: "Begin",
            timeoutEnd: "End",
            timeoutTitle: "Title",
            timeoutAlready: "Already"
        };

        if(typeof wb !== 'undefined') {
            let i18n = wb.i18n;
            this.i18nText = {
                buttonContinue: i18n( "st-btn-cont" ),
                buttonEnd: i18n( "st-btn-end" ),
                buttonSignin: i18n( "tmpl-signin" ),
                timeoutBegin: i18n( "st-to-msg-bgn" ),
                timeoutEnd: i18n( "st-to-msg-end" ),
                timeoutTitle: i18n( "st-msgbx-ttl" ),
                timeoutAlready: i18n( "st-alrdy-to-msg" )
            };
        }

        this.tick();
        this.timer = setInterval(this.tick.bind(this), 1000);
    }

    tick() {
        let now = Date.now();
        let time = this.getTime(this.settings.expireAt - now)
        if(this.settings.expireAt <= now) {
            console.log('Session as expired');
            clearInterval(this.timer);
            return;
        }
        if(this.settings.expireAt < now+180000) {
            clearInterval(this.timer);
            this.updateInactivityTimer();
            this.showInactivityAlert();
            return;
        }
    }

    hideInactivityAlert() {
        let dialog = document.getElementById(this.id);
        dialog.close();
    }

    showInactivityAlert() {
        let dialog = document.getElementById(this.id);

        let btn = dialog.querySelector('#laminas_expireSessionDialog_logout');
        btn.addEventListener('click', this.logout.bind(this));

        btn = dialog.querySelector('#laminas_expireSessionDialog_continue')
        btn.addEventListener('click', this.continueSession.bind(this));

        dialog.showModal();
    }

    updateInactivityTimer() {
        let now = Date.now();
        let time = this.getTime( this.settings.expireAt - now )
        let modal = document.getElementById('laminas_expireSessionDialog');
        modal.querySelector('.min').innerHTML = time.minutes;
        modal.querySelector('.sec').innerHTML = time.seconds;
        setTimeout(this.updateInactivityTimer.bind(this), 1000);
    }

    logout(e) {
        e.preventDefault();
        e.stopPropagation();

        if(this.settings.logoutEvent) {
            const event = new CustomEvent(this.settings.logoutEvent, {
                bubbles: true,
                detail: { },
            });
            document.body.dispatchEvent(event);
        }
        if(this.settings.logoutCallback) {
            this.settings.logoutCallback();
        }
        if(this.settings.logoutUrl) {
            window.location.href = this.settings.logoutUrl;
        }
        this.hideInactivityAlert();
        return false;
    }

    continueSession(e) {
        e.preventDefault();
        e.stopPropagation();
        if(this.settings.continueSessionEvent) {
            const event = new CustomEvent(this.settings.continueSessionEvent, {
                bubbles: true,
                detail: { },
            });
            document.body.dispatchEvent(event);
        }
        if(this.settings.continueSessionCallback) {
            this.settings.continueSessionCallback();
        }
        if(this.settings.continueSessionAjaxUrl) {
            // need to create a User generic class to handle this
            fetch(this.settings.continueSessionAjaxUrl, {
            });
        }
        this.hideInactivityAlert();
        return false;
    }

    getTime(milliseconds) {
        var time = { minutes: "", seconds: "" };

        if ( milliseconds != null ) { //eslint-disable-line no-eq-null
            time.minutes = parseInt( ( milliseconds / 60000 ) % 60, 10 );
            time.seconds = parseInt( ( milliseconds / 1000 ) % 60, 10 );
        }
        return time;
    }
}
