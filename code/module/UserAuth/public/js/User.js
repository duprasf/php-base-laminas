/**
* A class for the User
*/
class User {
    options = {
        useSessionTimeout: true
    }

    get loggedIn() {
        return this.isLoggedIn();
    }
    get userId() {
        let payload = this.getJwtPayload();
        return payload.id;
    }

    constructor(options) {
        this.options={};
        for(let i in options) {
            this.options[i] = options[i];
        }

        ready(function() {
            laminas.user.timeoutPing = setInterval(laminas.user.ping.bind(laminas.user), 600000);
            laminas.user.ping();
        });
    }

    setOption(name, value) {
        this.options[name] = value;
        return this;
    }

    getOption(name) {
        return this.options[name];
    }

    setupAppButtons() {
        if(!laminas.isApp) {
            return;
        }
        if(!document.querySelector('ul.app-list-account.list-unstyled')) {
            // just in case the CDTS is not completely loaded when we arrive here
            setTimeout(function(){laminas.user.setupAppButtons();}, 100);
            return;
        }
        if(!laminas.user.isLoggedIn()) {
            if(laminas.signInCallback) {
                this.addSignInBtn(laminas.signInCallback);
            }
            return;
        }
        this.addUserSettingsBtn(laminas.userSettingCallback);
        this.addSignOutBtn(laminas.signOutCallback);
    }

    addSignInBtn(callback) {
        if(!laminas.signInCallback || typeof(laminas.signInCallback) != 'function') {
            return;
        }

        let cdtsSignInBtn = document.getElementById('cdts-signin-btn');
        if(cdtsSignInBtn.getAttribute("href")=="") {
            cdtsSignInBtn.parentNode.removeChild(cdtsSignInBtn);
        } else if(cdtsSignInBtn) {
            return;
        }

        let a=document.createElement('a');
        a.id="cdts-signin-btn";
        a.classList.add('btn');
        a.href="#";
        a.innerHTML='<span class="glyphicon glyphicon-off" aria-hidden="true"></span>&nbsp;'+laminas.strings['Sign in']+'</a>';

        let ul = document.querySelector("ul.app-list-account");
        let li = document.createElement('li');
        li.appendChild(a);
        ul.appendChild(li);

        a.addEventListener('click', callback);
    }

    addSignOutBtn(callback) {
        let cdtsSignInBtn = document.getElementById('cdts-signin-btn');
        if(cdtsSignInBtn) {
            return;
        }
        if(!(laminas.signInCallback && typeof(laminas.signInCallback) == 'function')) {
            return;
        }

        let a=document.createElement('a');
        a.id="cdts-signin-btn";
        a.classList.add('btn');
        a.href="#";
        a.innerHTML='<span class="glyphicon glyphicon-off" aria-hidden="true"></span>&nbsp;'+laminas.strings['Sign in']+'</a>';

        let ul = document.querySelector("ul.app-list-account");
        let li = document.createElement('li');
        li.appendChild(a);
        ul.appendChild(ul);

        a.addEventListener('click', callback);
    }

    addUserSettingsBtn(callback) {
        let cdtsSignInBtn = document.getElementById('cdts-signin-btn');
        if(cdtsSignInBtn) {
            return;
        }
        if(!(laminas.signInCallback && typeof(laminas.signInCallback) == 'function')) {
            return;
        }

        let a=document.createElement('a');
        a.id="cdts-signin-btn";
        a.classList.add('btn');
        a.href="#";
        a.innerHTML='<span class="glyphicon glyphicon-cog" aria-hidden="true"></span>&nbsp;'+laminas.strings['Sign in']+'</a>';

        let ul = document.querySelector("ul.app-list-account");
        let li = document.createElement('li');
        li.appendChild(a);
        ul.appendChild(ul);

        a.addEventListener('click', signInCallback);
    }

    /**
    * @return bool, true if logged in and false if not logged in or if the JWT expired
    */
    isLoggedIn() {
        if(!this.getJwt()) {
            return false;
        }
        let payload = this.getJwtPayload();
        return payload.exp > (Date.now()/1000);
    }

    handleLogin(jwt, remember) {
        if(!jwt) {
            if(laminas.user.getOption('verbose')) {
                console.log('handleLogin received an empty jwt, loging out');
            }
            return laminas.user.logout();
        }
        this.saveJwt(jwt, remember);
        document.body.addEventListener("jwt-expired", e=>laminas.user.logout.bind(laminas.user));
        // Set a time out for when the JWT expires.
        // The default behavior does not logout the user automatically in case the
        // app wants to do something before end. It would be easy to set a call
        // a few minutes before to alert the user to extends the session for example.
        let payload = laminas.user.getJwtPayload();
        let time = (payload.exp*1000)-Date.now();
        if(time < 100) {
            // a minimum of 100 ms should be used for the call back
            // if the token is expired
            time = 100;
        }
        if(time <= 100) {
            if(laminas.user.getOption('verbose')) {
                console.log('The jwt is expired. Loggin out.');
            }
            return laminas.user.logout();
        }
        // the same timeout is set when a user is logged in and timeout ends on logout
        laminas.user.jwtTimeout = setTimeout(laminas.user.jwtExpired.bind(laminas.user), time);

        for(var key in payload) {
            if(!laminas.user[key]) {
                if(key=='id') {
                    continue;
                }
                laminas.user[key] = payload[key];
            }
        }

        document.body.classList.add('isLoggedIn');
        laminas.user.setupAppButtons();

        if(laminas.user.options.useSession) {
            laminas.user.startSession();
        }
    }

    ping() {
        let options = {
            method: 'get',
            headers: {
                'Content-Type': 'application/json',
                'X-Access-Token': this.getJwt()
            }
        };
        let pingUrl = this.getOption('pingUrl')??'/ping';

        fetch(pingUrl, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not OK');
                }
                return response.json();
            })
            .then( response => {
                if(laminas.user.getOption('verbose')) {
                    console.log('ping successful. JWT is:', response.jwt);
                }
                if(!response.jwt) {
                    return;
                }
                laminas.user.handleLogin(response.jwt, laminas.user.getRemembered())
            })
            .catch( error => {
                console.error(error);
            }
        );
    }

    /**
    * Let the app know that the JWT has expired and should take the
    * appropriate actions like logout the user
    */
    jwtExpired(){
        clearTimeout(this.jwtTimeout);
        const event = new CustomEvent("jwt-expired", {
            bubbles: true,
            detail: { },
        });
        ready(function(){
            document.body.dispatchEvent(event);
        });
    }

    logout() {
        if(laminas.user.getOption('verbose')) {
            console.log('laminas.user.logout()');
        }
        clearTimeout(this.jwtTimeout);

        let payload = this.getJwtPayload();
        for(var key in payload) {
            if(!this[key]) {
                this[key] = payload[key];
            }
        }

        sessionStorage.removeItem('jwt');
        localStorage.removeItem('jwt');
        this.jwtPayload=null;
    }

    startSession() {
        let payload = this.getJwtPayload();
        Session.getSession({
            expireAt: payload.exp*1000,
            continueSessionCallback: this.renewSession.bind(this),
            logoutEvent: this.logout.bind(this)
        });
    }

    renewSession() {
        return false;
    }

    saveJwt(jwt, remember) {
        if(laminas.user.getOption('verbose')) {
            console.log('laminas.user.saveJwt()');
        }
        if(remember == undefined) {
            remember = laminas.user.getRemembered() ? true : false;
        }

        sessionStorage.removeItem('jwt');
        localStorage.removeItem('jwt');

        if(!jwt) {
            return;
        }

        if(!remember) {
            // if not "remember" save in session until browser stops
            sessionStorage.setItem('jwt', jwt);
        } else {
            // if clicked to remember, save in local storage valid untile it expires
            localStorage.setItem('jwt', jwt);
        }

        if(this.options.useSession) {
            this.startSession();
        }
    }

    getJwt() {
        return localStorage.getItem('jwt') ?? sessionStorage.getItem('jwt') ?? null;
    }

    getRemembered() {
        return localStorage.getItem('jwt') ? true : (sessionStorage.getItem('jwt') ? false : null);
    }

    getJwtPayload () {
        if(!this.jwtPayload) {
            let token = this.getJwt();
            if(!token || typeof token == 'undefined'){
                return false;
            }
            let base64Url = token.split('.')[1];
            let base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
            let jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
                return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(''));

            this.jwtPayload = JSON.parse(jsonPayload);
        }
        return this.jwtPayload;
    };
}

laminas.user = new User({
    useSession:false,// disabled until Session is fixed
});
