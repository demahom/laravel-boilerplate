import 'bootstrap'
import 'slick-carousel'
import 'intl-tel-input'
import 'pwstrength-bootstrap/dist/pwstrength-bootstrap'
import 'cookieconsent'
import swal from 'sweetalert2'
import WebFont from 'webfontloader'

/**
 * JS Settings App
 */
let jsonSettings = $('[data-settings-selector="settings-json"]').text()
window.settings = jsonSettings ? JSON.parse(jsonSettings) : {}

window.swal = swal

/**
 * Font
 */
WebFont.load({
  google: {
    families: ['Roboto']
  }
})

/**
 * Cookie Consent
 */
window.addEventListener('load', () => {
  window.cookieconsent.initialise({
    'palette': {
      'popup': {
        'background': '#fff',
        'text': '#777'
      },
      'button': {
        'background': '#3097d1',
        'text': '#ffffff'
      }
    },
    'showLink': false,
    'theme': 'edgeless',
    'content': {
      'message': window.settings.cookieconsent.message,
      'dismiss': window.settings.cookieconsent.dismiss
    }
  })
});

/**
 * jQuery Plugins Init
 */
(($) => {
  window.locale = $('html').attr('lang')

  /**
   * Place the CSRF token as a header on all pages for access in AJAX requests
   */
  $.ajaxSetup({
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
  })

  /**
   * Slick
   */
  $('[data-toggle="slider"]')
    .not('.slick-initialized')
    .slick({
      dots: true,
      infinite: true,
      speed: 300,
      slidesToShow: 3,
      slidesToScroll: 1,
      responsive: [
        {
          breakpoint: 768,
          settings: {
            slidesToShow: 2
          }
        },
        {
          breakpoint: 576,
          settings: {
            slidesToShow: 1
          }
        }
      ]
    })

  /**
   * Bind all bootstrap tooltips
   */
  $('[data-toggle="tooltip"]').tooltip()

  /**
   * Bind all bootstrap popovers
   */
  $('[data-toggle="popover"]').popover()

  /**
   * Bind all swal confirm buttons
   */
  $('[data-toggle="confirm"]').click((e) => {
    e.preventDefault()

    window.swal({
      title: $(e.currentTarget).attr('data-trans-title'),
      type: 'warning',
      showCancelButton: true,
      cancelButtonText: $(e.currentTarget).attr('data-trans-button-cancel'),
      confirmButtonColor: '#dd4b39',
      confirmButtonText: $(e.currentTarget).attr('data-trans-button-confirm')
    }).then((result) => {
      if (result.value) {
        $(e.target).closest('form').submit()
      }
    })
  })

  $('[data-toggle="password-strength-meter"]').pwstrength({
    ui: {
      bootstrap4: true
    }
  })

  $('[type="tel"]').intlTelInput({
    autoPlaceholder: 'aggressive',
    utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/11.0.14/js/utils.js',
    initialCountry: window.locale === 'en' ? 'us' : window.locale,
    preferredCountries: ['us', 'gb', 'fr']
  })

  /**
   * Bootstrap tabs nav specific hash manager
   */
  let hash = document.location.hash
  let tabanchor = $('.nav-tabs a:first')

  if (hash) {
    tabanchor = $(`.nav-tabs a[href="${hash}"]`)
  }

  tabanchor.tab('show')

  $('a[data-toggle="tab"]').on('show.bs.tab', (e) => {
    window.location.hash = e.target.hash
  })
})(jQuery)
