if(!ready){
    function ready(fn) {
        if (document.readyState != 'loading'){
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }
}

ready(initUserRegistration);
wb.doc.on("wb-ready.wb-frmvld", ".wb-frmvld", function (event) {
    if(typeof passwordRules === 'undefined' || !Object.keys(passwordRules).length) {
        if(console && console.log) {
            console.log('The variable passwordRules is not defined, password will not be able to validate');
        }
        passwordRules={};
    }
    if(typeof passwordRulesTranslation === 'undefined' || !Object.keys(passwordRulesTranslation).length) {
        passwordRulesTranslation={};
    }

    if(passwordRules.minSize) {
        jQuery.validator.addMethod(
            "validPasswordLength",
            function(password,element){return password.length >= passwordRules.minSize;},
            passwordRulesTranslation.minSize ? passwordRulesTranslation.minSize : "Your password must contain at least "+passwordRules.minSize+" characters."
        );
        $('#password').rules("add",  {"validPasswordLength":passwordRules.minSize});
    }
    if(passwordRules.atLeastOneLowerCase) {
        jQuery.validator.addMethod(
            "validPasswordLowerCase",
            function(password,element){return password.match(/[a-z]/);},
            passwordRulesTranslation.atLeastOneLowerCase ? passwordRulesTranslation.atLeastOneLowerCase : "Your password must contain at least one lower case character."
        );
        $('#password').rules("add",  {"validPasswordLowerCase":true});
    }
    if(passwordRules.atLeastOneUpperCase) {
        jQuery.validator.addMethod(
            "validPasswordUpperCase",
            function(password,element){return password.match(/[A-Z]/);},
            passwordRulesTranslation.atLeastOneUpperCase ? passwordRulesTranslation.atLeastOneUpperCase : "Your password must contain at least one upper case character."
        );
        $('#password').rules("add",  {"validPasswordUpperCase":true});
    }
    if(passwordRules.atLeastOneNumber) {
        jQuery.validator.addMethod(
            "validPasswordOneNumber",
            function(password,element){return password.match(/[0-9]/);},
            passwordRulesTranslation.atLeastOneNumber ? passwordRulesTranslation.atLeastOneNumber : "Your password must contain at least one number."
        );
        $('#password').rules("add",  {"validPasswordOneNumber":true});
    }
    if(passwordRules.atLeastOneSpecialCharacters) {
        var escaped = RegExp.escape(passwordRules['atLeastOneSpecialCharacters']);
        var regex = new RegExp('['+escaped+']');
        jQuery.validator.addMethod(
            "validPasswordSpecialCharacter",
            function(password,element){return !!regex.exec(password);},
            passwordRulesTranslation.atLeastOneSpecialCharacters ? passwordRulesTranslation.atLeastOneSpecialCharacters : "Your password must contain at least one special character."
        );
        $('#password').rules("add",  {"validPasswordSpecialCharacter":true});
    }

    jQuery.validator.addMethod(
        "validPasswordConfirmation",
        function(confirm,element){return confirm == document.getElementById('password').value;},
        passwordRulesTranslation.confirmDoesNotMatch ? passwordRulesTranslation.confirmDoesNotMatch : "Your password and confirmation must match."
    );
    $('#confirmPassword').rules("add",  {"validPasswordConfirmation":true});
});

function initUserRegistration() {
    //document.getElementById('validation-example').addEventListener('submit', validate);
}

// polyfill for RegExp.escape
if(!RegExp.escape){
    RegExp.escape = function(s){
      return String(s).replace(/[\\^$*+?.()|[\]{}]/g, '\\$&');
    };
}
