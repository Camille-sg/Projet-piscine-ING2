/* ═══════════════════════════════════════════════════════
   data.js — CampusPlay v3  (MySQL + MAMP/XAMPP)
   ═══════════════════════════════════════════════════════ */

const API = 'api/';

/* ── Utilitaires ──────────────────────────────────────── */
function e(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function formatDate(str) {
  if (!str) return '';
  return new Date(str).toLocaleDateString('fr-FR', {
    day: '2-digit', month: 'long', year: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

/* ── Flash messages ───────────────────────────────────── */
function flash(msg, type) {
  type = type || 'succes';
  localStorage.setItem('cp_flash', JSON.stringify({ msg: msg, type: type }));
}

function afficheFlash(conteneur) {
  if (!conteneur) return;
  var f = localStorage.getItem('cp_flash');
  if (!f) return;
  var obj = JSON.parse(f);
  localStorage.removeItem('cp_flash');
  conteneur.innerHTML = '<div class="flash flash-' + obj.type + '">' + e(obj.msg) + '</div>';
  setTimeout(function() { if (conteneur) conteneur.innerHTML = ''; }, 3500);
}

/* ── Session ──────────────────────────────────────────── */
function getUser() {
  // Nouveau système (sessionStorage)
  var u = JSON.parse(sessionStorage.getItem('cp_user') || 'null');
  if (u) return u;
  // Ancien système (localStorage) — compatibilité
  u = JSON.parse(localStorage.getItem('campusplay_user') || 'null');
  if (u) return u;
  return null;
}

function isConnecte() {
  return getUser() !== null;
}

function getRole() {
  var u = getUser();
  return u ? u.role : '';
}

function setUser(u) {
  sessionStorage.setItem('cp_user', JSON.stringify(u));
}

function deconnecter() {
  sessionStorage.removeItem('cp_user');
  localStorage.removeItem('campusplay_user');
  try { fetch(API + 'auth.php?action=logout', { credentials: 'include' }); } catch(err) {}
  window.location.href = 'index.html';
}

/* ── Chargement synchrone des données ─────────────────── */
function _sync(url) {
  try {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.withCredentials = true;
    xhr.send();
    if (xhr.status === 200) {
      return JSON.parse(xhr.responseText);
    }
    return [];
  } catch(err) {
    console.warn('Erreur chargement ' + url + ':', err);
    return [];
  }
}

window.ACTIVITES  = _sync(API + 'activites.php');
window.EVENEMENTS = _sync(API + 'evenements.php');
window.RESSOURCES = _sync(API + 'ressources.php');

/* ── Authentification ─────────────────────────────────── */
async function connexion(email, mdp) {
  var r = await fetch(API + 'auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email: email, mdp: mdp }),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) setUser(data.user);
  return data;
}

async function inscription(prenom, nom, email, mdp) {
  var r = await fetch(API + 'auth.php?action=inscription', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prenom: prenom, nom: nom, email: email, mdp: mdp }),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) setUser(data.user);
  return data;
}

/* ── Événements ───────────────────────────────────────── */
async function _postEvenement(payload) {
  var u = getUser();
  payload._user = u ? u.email : '';
  var r = await fetch(API + 'evenements.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) {
    window.EVENEMENTS = _sync(API + 'evenements.php');
  }
  return data;
}

function creerEvenement(donnees) {
  donnees.action = 'creer';
  return _postEvenement(donnees);
}

function supprimerEvenement(id) {
  return _postEvenement({ action: 'supprimer', id: id });
}

function approuverEvenement(id) {
  return _postEvenement({ action: 'statut', id: id, statut: 'approuve' });
}

function refuserEvenement(id) {
  return _postEvenement({ action: 'statut', id: id, statut: 'refuse' });
}

/* ── Inscriptions ─────────────────────────────────────── */
function getInscriptions() {
  return _sync(API + 'inscriptions.php');
}

function estInscrit(evenement_id) {
  var u = getUser();
  if (!u) return false;
  var liste = getInscriptions();
  for (var i = 0; i < liste.length; i++) {
    if (liste[i].evenement_id == evenement_id && liste[i].user_email === u.email) {
      return true;
    }
  }
  return false;
}

async function sInscrireEvenement(evenement_id) {
  var u = getUser();
  var r = await fetch(API + 'inscriptions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'inscrire',
      evenement_id: evenement_id,
      _user: u ? u.email : ''
    }),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) window.EVENEMENTS = _sync(API + 'evenements.php');
  return data;
}

async function seDesinscrire(evenement_id) {
  var u = getUser();
  var r = await fetch(API + 'inscriptions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      action: 'desinscrire',
      evenement_id: evenement_id,
      _user: u ? u.email : ''
    }),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) window.EVENEMENTS = _sync(API + 'evenements.php');
  return data;
}

/* ── Réservations ─────────────────────────────────────── */
function getReservations() {
  return _sync(API + 'reservations.php');
}

async function creerReservation(donnees) {
  var u = getUser();
  donnees.action = 'creer';
  donnees._user  = u ? u.email : '';
  var r = await fetch(API + 'reservations.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(donnees),
    credentials: 'include'
  });
  return r.json();
}

async function _statutReservation(id, statut) {
  var u = getUser();
  var r = await fetch(API + 'reservations.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'statut', id: id, statut: statut, _user: u ? u.email : '' }),
    credentials: 'include'
  });
  return r.json();
}

function approuverReservation(id) { return _statutReservation(id, 'approuve'); }
function refuserReservation(id)   { return _statutReservation(id, 'refuse'); }
function annulerReservation(id)   { return _statutReservation(id, 'annule'); }

/* ── Comptes (admin) ──────────────────────────────────── */
function getComptes() {
  return _sync(API + 'comptes.php');
}

async function modifierRoleCompte(id, role) {
  var u = getUser();
  var r = await fetch(API + 'comptes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'role', id: id, role: role, _user: u ? u.email : '' }),
    credentials: 'include'
  });
  return r.json();
}

async function supprimerCompte(id) {
  var u = getUser();
  var r = await fetch(API + 'comptes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'supprimer', id: id, _user: u ? u.email : '' }),
    credentials: 'include'
  });
  return r.json();
}

async function creerRessource(donnees) {
  var u = getUser();
  donnees.action = 'creer';
  donnees._user  = u ? u.email : '';
  var r = await fetch(API + 'ressources.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(donnees),
    credentials: 'include'
  });
  var data = await r.json();
  if (data.succes) window.RESSOURCES = _sync(API + 'ressources.php');
  return data;
}

/* ── Navbar ───────────────────────────────────────────── */
function renderNavbar(actif) {
  var user = getUser();
  if (!user) return;

  var isAdmin   = user.role === 'admin' || user.role === 'manager';
  var roleColors = { admin: '#f59e0b', manager: '#10b981', etudiant: '#6366f1' };
  var roleColor  = roleColors[user.role] || '#94a3b8';

  var sidebar = document.createElement('nav');
  sidebar.style.cssText = [
    'position:fixed', 'left:0', 'top:0', 'bottom:0', 'width:220px',
    'background:#1e293b', 'padding:1.25rem .85rem',
    'display:flex', 'flex-direction:column', 'gap:.2rem',
    'z-index:200', 'overflow-y:auto'
  ].join(';');

  sidebar.innerHTML =
    '<div style="display:flex;align-items:center;gap:.65rem;padding:.4rem 0 1.1rem;' +
    'border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:.4rem">' +
      '<span style="font-size:1.6rem">🎵</span>' +
      '<div>' +
        '<div style="color:white;font-weight:700;font-size:.9rem;line-height:1.2">CampusPlay</div>' +
        '<div style="color:#cbd5e1;font-size:.75rem">' + e(user.prenom) + ' ' + e(user.nom) + '</div>' +
        '<div style="font-size:.68rem;font-weight:700;text-transform:uppercase;color:' + roleColor + '">' + e(user.role) + '</div>' +
      '</div>' +
    '</div>';

  var liens = [
    { href: 'accueil.html',       ico: '🏠', label: 'Accueil',       key: 'accueil' },
    { href: 'activites.html',     ico: '🎵', label: 'Activités',     key: 'activites' },
    { href: 'evenements.html',    ico: '📅', label: 'Événements',    key: 'evenements' },
    { href: 'ressources.html',    ico: '📦', label: 'Ressources',    key: 'ressources' },
    { href: 'reservations.html',  ico: '🔖', label: 'Réservations',  key: 'reservations' },
    null,
    { href: 'notifications.html', ico: '🔔', label: 'Notifications', key: 'notifications' },
    null,
    { href: 'profil.html',        ico: '👤', label: 'Mon profil',    key: 'profil' }
  ];
  if (isAdmin) liens.push({ href: 'admin.html', ico: '⚙️', label: 'Admin', key: 'admin' });

  for (var i = 0; i < liens.length; i++) {
    var lk = liens[i];
    if (!lk) {
      var hr = document.createElement('hr');
      hr.style.cssText = 'border:none;border-top:1px solid rgba(255,255,255,.07);margin:.3rem 0';
      sidebar.appendChild(hr);
      continue;
    }
    var a  = document.createElement('a');
    a.href = lk.href;
    var on = lk.key === actif;
    a.style.cssText = [
      'display:flex', 'align-items:center', 'gap:.55rem',
      'padding:.5rem .7rem', 'border-radius:.45rem',
      'color:' + (on ? 'white' : '#94a3b8'),
      'background:' + (on ? 'rgba(79,70,229,.45)' : 'transparent'),
      'text-decoration:none', 'font-size:.85rem', 'transition:.15s'
    ].join(';');
    a.innerHTML = lk.ico + ' ' + lk.label;
    (function(el, isActif) {
      el.onmouseover = function() { if (!isActif) { el.style.background = 'rgba(255,255,255,.07)'; el.style.color = 'white'; } };
      el.onmouseout  = function() { if (!isActif) { el.style.background = 'transparent'; el.style.color = '#94a3b8'; } };
    })(a, on);
    sidebar.appendChild(a);
  }

  var btn = document.createElement('button');
  btn.style.cssText = [
    'margin-top:auto', 'padding:.5rem .7rem', 'border-radius:.45rem',
    'background:rgba(239,68,68,.12)', 'color:#fca5a5',
    'border:none', 'cursor:pointer', 'font-size:.85rem',
    'text-align:left', 'width:100%'
  ].join(';');
  btn.innerHTML  = '🚪 Déconnexion';
  btn.onclick    = deconnecter;
  sidebar.appendChild(btn);

  document.body.prepend(sidebar);
  document.body.style.paddingLeft = '228px';
}