import { Controller } from '@hotwired/stimulus';

//?TinyMCE self-heberge (licence GPL, cf. JobPostCrudController) est charge par EasyAdmin
//?via un <script> classique (addJsFiles), pas par ce bundle webpack : ses skins/icones ne
//?sont pas concus pour transiter par un bundler JS. On attend donc que "window.tinymce"
//?existe avant d'initialiser l'editeur, sans hypothese sur l'ordre de chargement des scripts.
export default class extends Controller {
    connect() {
        this.waitForTinyMce(() => this.initEditor());
    }

    disconnect() {
        if (window.tinymce) {
            window.tinymce.remove(this.element);
        }
    }

    waitForTinyMce(callback) {
        if (window.tinymce) {
            callback();
            return;
        }

        const interval = setInterval(() => {
            if (window.tinymce) {
                clearInterval(interval);
                callback();
            }
        }, 50);
    }

    initEditor() {
        window.tinymce.init({
            target: this.element,
            license_key: 'gpl',
            promotion: false,
            branding: false,
            menubar: false,
            height: 400,
            base_url: '/build/tinymce',
            suffix: '.min',
            plugins: 'lists link table advlist',
            toolbar: 'undo redo | blocks | bold italic underline strikethrough forecolor backcolor | bullist numlist | link table | removeformat',
            color_map: [
                '5F49CE', 'Violet',
                '8F75DB', 'Violet clair',
                '00BB9D', 'Vert (accent)',
                '1C1C3E', 'Bleu nuit (texte)',
                'F8F1E9', 'Creme (fond)',
                'FFFFFF', 'Blanc',
                '000000', 'Noir',
            ],
            content_style: "body { font-family: 'Nunito', sans-serif; font-size: 15px; color: #1C1C3E; }",
        });
    }
}
