/* discover.js — shared behaviour for articles_list.php, article.php and training.php
   - tgl(): the language button in the shared menu (nav.php) flips the page
     direction; bi-en / bi-ar texts and the page title follow it.
   - sections rise in as they scroll into view
   - the background video fades in once it can play */
document.documentElement.classList.add('js');

function owSetTitle(){
  var h = document.documentElement;
  var t = h.getAttribute('dir') === 'rtl' ? h.dataset.titleAr : h.dataset.titleEn;
  if (t) document.title = t;
}
function tgl(){
  var h = document.documentElement, ar = h.getAttribute('dir') !== 'rtl';
  h.setAttribute('dir', ar ? 'rtl' : 'ltr');
  h.setAttribute('lang', ar ? 'ar' : 'en');
  try { localStorage.setItem('lang', ar ? 'ar' : 'en'); } catch (e) {}
  owSetTitle();
}

document.addEventListener('DOMContentLoaded', function(){
  owSetTitle();

  var els = document.querySelectorAll('.reveal');
  if (!('IntersectionObserver' in window)) {
    els.forEach(function(el){ el.classList.add('in'); });
  } else {
    var io = new IntersectionObserver(function(entries){
      entries.forEach(function(en){ if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -6% 0px' });
    els.forEach(function(el){ io.observe(el); });
  }

  var v = document.getElementById('vid');
  if (v) {
    v.addEventListener('canplay', function(){ v.classList.add('on'); }, { once: true });
    var p = v.play(); if (p && p.catch) p.catch(function(){});
  }
});
