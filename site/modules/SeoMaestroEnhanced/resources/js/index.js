import '../scss/styles.scss';
import InputfieldGooglePreview from "./components/InputfieldGooglePreview";
import InputfieldFacebookSharePreview from './components/InputfieldFacebookSharePreview';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-seomaestroenhanced-googlepreview]').forEach(($elem) => {
        const googlePreview = new InputfieldGooglePreview($elem, $elem.dataset.seomaestroEnhancedGooglepreview);
        googlePreview.init();
    });

    document.querySelectorAll('[data-seomaestroenhanced-facebookpreview]').forEach(($elem) => {
        const facebookSharePreview = new InputfieldFacebookSharePreview(
          $elem,
          $elem.dataset.seomaestroEnhancedFacebookpreview
        );
        facebookSharePreview.init();
    });

    const inheritWrappers = document.querySelectorAll('[data-seomaestroenhanced-metadata-inherit]');

    inheritWrappers.forEach(($wrapper) => {
        $wrapper.addEventListener('change', (event) => {
            if (event.target.checked) {
                return;
            }

            const inputName = $wrapper.dataset.seomaestroEnhancedMetadataInherit;
            const $input = document.querySelector(`input[name="${inputName}"], textarea[name="${inputName}"]`);
            if ($input) {
                $input.focus();
            }
        });
    });
});
