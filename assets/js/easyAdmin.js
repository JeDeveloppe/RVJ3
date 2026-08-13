//?Necessaire pour que les controleurs Stimulus (ex: la carte Leaflet des statistiques)
//?s'initialisent sur les pages admin - absent jusqu'ici, ce fichier ne demarrait pas Stimulus.
import './bootstrap.js';

let switchBoiteIsDetachee = document.getElementById('Boite_isOnline');
let switchBoiteIsOccasion = document.getElementById('Boite_isOccasion');

//?Sauvegarde le formulaire en arriere-plan (sans recharger la page) en simulant un clic sur
//?"Enregistrer et continuer" via fetch, plutot qu'un vrai .click() qui navigue.
function saveFormInBackground(switchElement) {
    let form = switchElement.closest('form');
    let saveAndContinuButton = document.getElementsByClassName('action-saveAndContinue')[0];

    if (!form || !saveAndContinuButton) {
        return;
    }

    fetch(form.action || window.location.href, {
        method: 'POST',
        body: new FormData(form, saveAndContinuButton),
    });
}

if(switchBoiteIsDetachee){

    switchBoiteIsDetachee.addEventListener('change', function() {
        saveFormInBackground(switchBoiteIsDetachee);
    });
}

if(switchBoiteIsOccasion){

    switchBoiteIsOccasion.addEventListener('change', function() {
        saveFormInBackground(switchBoiteIsOccasion);
    });
}
