/* ═══════════════════════════════════════════════
   data.js — CampusPlay v3  (MySQL + MAMP)
   ─────────────────────────────────────────────── */

const API = 'api/';

/* ── Utilitaires ──────────────────────────────── */
function e(str) {
  if (str === null || str === undefined) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function formatDate(str) {
  if (!str) return '';
  return new Date(str).toLocaleDateString('fr-FR', {
    day:'2-digit', month:'long', year:'numeric',
    hour:'2-digit', minute:'2-digit'
  });
}

/* ── Flash messages ───────────────────────────── */
function flash(msg, type = 'succes') {
  localStorage.setItem('cp_flash', JSON.stringify({ msg, type }));
}

function afficheFlash(conteneur) {
  if (!conteneur) return;
  const f = localStorage.getItem('cp_flash');
  if (!f) return;
  const { msg, type } = JSON.parse(f);
  localStorage.removeItem('cp_flash');
  conteneur.innerHTML = `<div class="flash flash-${type}">${e(msg)}</div>`;
  setTimeout(() => { if (conteneur) conteneur.innerHTML = ''; }, 3500);
}

/* ── Session (sessionStorage) ─────────────────── */
function getUser()  { return JSON.parse(sessionStorage.getItem('cp_user') || 'null'); }
function isConnecte() { return getUser() !== null; }
function getRole()  { return getUser()?.role || ''; }
function setUser(u) { sessionStorage.setItem('cp_user', JSON.stringify(u)); }

function deconnecter() {
  sessionStorage.removeItem('cp_user');
  fetch(API + 'auth.php?action=logout', { credentials: 'include' }).catch(() => {});
  window.location.href = 'index.html';
}

/* ── Chargement synchrone des données ─────────── */
// ⚠️ Fonctionne uniquement depuis MAMP (pas en file://)
function _sync(url) {
  try {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', url, false);
    xhr.withCredentials = true;
    xhr.send();
    return JSON.parse(xhr.responseText);
  } catch(err) {
    console.warn('Erreur chargement:', url, err);
    return [];
  }
}

window.ACTIVITES  = _sync(API + 'activites.php');
window.EVENEMENTS = _sync(API + 'evenements.php');
window.RESSOURCES = _sync(API + 'ressources.php');

/* ── Auth ─────────────────────────────────────── */
async function connexion(email, mdp) {
  const r = await fetch(API + 'auth.php?action=login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, mdp }),
    credentials: 'include',
  });
  const data = await r.json();
  if (data.succes) setUser(data.user);
  return data;
}

async function inscription(prenom, nom, email, mdp) {
  const r = await fetch(API + 'auth.php?action=inscription', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ prenom, nom, email, mdp }),
    credentials: 'include',
  });
  const data = await r.json();
  if (data.succes) setUser(data.user);
  return data;
}

/* ── Événements ───────────────────────────────── */
async function _postEv(payload) {
  const r = await fetch(API + 'evenements.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
    credentials: 'include',
  });
  const data = await r.json();
  if (data.succes) window.EVENEMENTS = _sync(API + 'evenements.php');
  return data;
}

function creerEvenement(d)    { return _postEv({ action:'creer',    ...d }); }
function supprimerEvenement(id){ return _postEv({ action:'supprimer', id }); }
function approuverEvenement(id){ return _postEv({ action:'statut', id, statut:'approuve' }); }
function refuserEvenement(id)  { return _postEv({ action:'statut', id, statut:'refuse' }); }

/* ── Inscriptions ─────────────────────────────── */
function getInscriptions() { return _sync(API + 'inscriptions.php'); }

function estInscrit(evenement_id) {
  const u = getUser();
  if (!u) return false;
  return getInscriptions().some(i =>
    parseInt(i.evenement_id) === parseInt(evenement_id) && i.user_email === u.email
  );
}

async function sInscrireEvenement(evenement_id) {
  const r = await fetch(API + 'inscriptions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'inscrire', evenement_id }),
    credentials: 'include',
  });
  const data = await r.json();
  if (data.succes) window.EVENEMENTS = _sync(API + 'evenements.php');
  return data;
}

async function seDesinscrire(evenement_id) {
  const r = await fetch(API + 'inscriptions.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'desinscrire', evenement_id }),
    credentials: 'include',
  });
  const data = await r.json();
  if (data.succes) window.EVENEMENTS = _sync(API + 'evenements.php');
  return data;
}

/* ── Réservations ─────────────────────────────── */
function getReservations() { return _sync(API + 'reservations.php'); }

async function creerReservation(d) {
  const r = await fetch(API + 'reservations.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'creer', ...d }),
    credentials: 'include',
  });
  return r.json();
}

async function _statutRes(id, statut) {
  const r = await fetch(API + 'reservations.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'statut', id, statut }),
    credentials: 'include',
  });
  return r.json();
}
function approuverReservation(id) { return _statutRes(id, 'approuve'); }
function refuserReservation(id)   { return _statutRes(id, 'refuse'); }
function annulerReservation(id)   { return _statutRes(id, 'annule'); }

/* ── Comptes (admin) ──────────────────────────── */
function getComptes() { return _sync(API + 'comptes.php'); }

async function modifierRoleCompte(id, role) {
  const r = await fetch(API + 'comptes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'role', id, role }),
    credentials: 'include',
  });
  return r.json();
}

async function supprimerCompte(id) {
  const r = await fetch(API + 'comptes.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ action: 'supprimer', id }),
    credentials: 'include',
  });
  return r.json();
}

/* ── Navbar ───────────────────────────────────── */
function renderNavbar(actif) {
  const user    = getUser();
  if (!user) return;
  const isAdmin = user.role === 'admin' || user.role === 'manager';
  const roleColor = { admin:'#f59e0b', manager:'#10b981', etudiant:'#6366f1' };

  const sidebar = document.createElement('nav');
  sidebar.style.cssText = `
    position:fixed;left:0;top:0;bottom:0;width:220px;
    background:#1e293b;padding:1.25rem .85rem;
    display:flex;flex-direction:column;gap:.2rem;
    z-index:200;overflow-y:auto;`;

  sidebar.innerHTML = `
    <div style="display:flex;align-items:center;gap:.65rem;padding:.4rem 0 1.1rem;
         border-bottom:1px solid rgba(255,255,255,.1);margin-bottom:.4rem">
      <span style="font-size:1.6rem">🎵</span>
      <div>
        <div style="color:white;font-weight:700;font-size:.9rem;line-height:1.2">CampusPlay</div>
        <div style="color:#cbd5e1;font-size:.75rem">${e(user.prenom)} ${e(user.nom)}</div>
        <div style="font-size:.68rem;font-weight:700;text-transform:uppercase;
             color:${roleColor[user.role]||'#94a3b8'}">${e(user.role)}</div>
      </div>
    </div>`;

  const liens = [
    { href:'accueil.html',       ico:'🏠', label:'Accueil',       key:'accueil' },
    { href:'activites.html',     ico:'🎵', label:'Activités',     key:'activites' },
    { href:'evenements.html',    ico:'📅', label:'Événements',    key:'evenements' },
    { href:'ressources.html',    ico:'📦', label:'Ressources',    key:'ressources' },
    { href:'reservations.html',  ico:'🔖', label:'Réservations',  key:'reservations' },
    null,
    { href:'notifications.html', ico:'🔔', label:'Notifications', key:'notifications' },
    null,
    { href:'profil.html',        ico:'👤', label:'Mon profil',    key:'profil' },
    isAdmin ? { href:'admin.html', ico:'⚙️', label:'Admin', key:'admin' } : null,
  ];

  liens.forEach(lk => {
    if (!lk) {
      const hr = document.createElement('hr');
      hr.style.cssText = 'border:none;border-top:1px solid rgba(255,255,255,.07);margin:.3rem 0';
      sidebar.appendChild(hr);
      return;
    }
    const a   = document.createElement('a');
    a.href    = lk.href;
    const on  = lk.key === actif;
    a.style.cssText = `
      display:flex;align-items:center;gap:.55rem;
      padding:.5rem .7rem;border-radius:.45rem;
      color:${on?'white':'#94a3b8'};
      background:${on?'rgba(79,70,229,.45)':'transparent'};
      text-decoration:none;font-size:.85rem;transition:.15s;`;
    a.innerHTML = `${lk.ico} ${lk.label}`;
    a.onmouseover = () => { if(!on){ a.style.background='rgba(255,255,255,.07)'; a.style.color='white'; } };
    a.onmouseout  = () => { if(!on){ a.style.background='transparent'; a.style.color='#94a3b8'; } };
    sidebar.appendChild(a);
  });

  const btn = document.createElement('button');
  btn.style.cssText = `
    margin-top:auto;padding:.5rem .7rem;border-radius:.45rem;
    background:rgba(239,68,68,.12);color:#fca5a5;
    border:none;cursor:pointer;font-size:.85rem;text-align:left;width:100%;`;
  btn.innerHTML = '🚪 Déconnexion';
  btn.onclick   = deconnecter;
  sidebar.appendChild(btn);

  document.body.prepend(sidebar);
  document.body.style.paddingLeft = '228px';
}