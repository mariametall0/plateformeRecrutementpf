<?php
require_once "../includes/layout.php";

// Protection candidat
check_role('candidat');

$id_candidat = $_SESSION["id"];

$candidatures = [];
try {
    $stmt = $pdo->prepare("
        SELECT c.id AS candidature_id, c.id_offre, c.statut, c.date_candidature,
               co.titre AS offre_titre, co.date_cloture,
               e.date_entrevue, e.heure_entrevue, e.lieu_ou_lien, e.notes
        FROM candidatures c
        JOIN offres co ON c.id_offre = co.id
        LEFT JOIN entretiens e ON e.id_candidature = c.id
        WHERE c.id_candidat = ?
        ORDER BY c.date_candidature DESC
    ");
    $stmt->execute([$id_candidat]);
    $candidatures = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Erreur silencieuse
}

include_header("Mes candidatures");
?>

<div class="row animate__animated animate__fadeIn">
    <div class="col-12 mb-4">
        <div class="p-4 bg-white rounded-4 shadow-sm border-start border-primary border-5 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1 fw-bold text-dark">Mes Candidatures</h1>
                <p class="text-muted mb-0">Suivez l'état d'avancement de vos dossiers en temps réel.</p>
            </div>
            <div class="d-none d-md-block text-primary display-6">📁</div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-4 py-3 border-0">Offre / Réf</th>
                            <th class="py-3 border-0">Date de dépôt</th>
                            <th class="py-3 border-0 text-center col-statut">Statut</th>
                            <th class="pe-4 py-3 border-0 text-end col-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidatures)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <div class="display-1 mb-3 opacity-25">📂</div>
                                    <p class="text-muted fw-bold">Vous n'avez pas encore postulé à un offre.</p>
                                    <a href="liste_offres.php" class="btn btn-primary rounded-pill px-4 mt-3">Explorer les opportunités</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidatures as $c): 
                                $status_badge = 'bg-warning-subtle text-warning border-warning';
                                $status_label = 'En attente';
                                if ($c['statut'] === 'validee') { $status_badge = 'bg-success-subtle text-success border-success'; $status_label = 'Validée'; }
                                elseif ($c['statut'] === 'rejetee') { $status_badge = 'bg-danger-subtle text-danger border-danger'; $status_label = 'Rejetée'; }
                            ?>
                                <tr>
                                    <td class="ps-4 py-4">
                                        <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($c['offre_titre']); ?></div>
                                        <div class="small text-muted mt-1">Dossier #<?php echo $c['candidature_id']; ?></div>
                                    </td>
                                    <td class="py-4">
                                        <div class="small text-secondary fw-semibold">
                                            <?php echo format_date($c['date_candidature']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center py-4 col-statut">
                                        <?php 
                                            $badge_theme = 'blue'; $badge_label = 'À l\'étude';
                                            if ($c['statut'] === 'validee') { $badge_theme = 'success'; $badge_label = 'Validée'; }
                                            elseif ($c['statut'] === 'rejetee') { $badge_theme = 'danger'; $badge_label = 'Rejetée'; }
                                        ?>
                                        <span class="badge bg-<?php echo $badge_theme; ?> bg-opacity-10 text-<?php echo $badge_theme; ?> border border-<?php echo $badge_theme; ?>-subtle rounded-pill">
                                            <?php echo $badge_label; ?>
                                        </span>
                                        
                                        <?php if (!empty($c['date_entrevue'])): ?>
                                        <div class="mt-2 small text-primary fw-bold">
                                            <i class="bi bi-calendar-event me-1"></i> Entretien / Épreuve
                                        </div>
                                        <div class="mt-1" style="font-size: 0.75rem;">
                                            <div>Le <?php echo format_date($c['date_entrevue']); ?> à <?php echo substr($c['heure_entrevue'], 0, 5); ?></div>
                                            <div class="text-truncate mt-1" style="max-width:150px; margin:auto;" title="<?php echo htmlspecialchars($c['lieu_ou_lien']); ?>">📍 <?php echo htmlspecialchars($c['lieu_ou_lien']); ?></div>
                                            <?php if (!empty($c['notes'])): ?>
                                                <div class="mt-2 p-1 rounded bg-light border-start border-primary border-2" style="font-size: 0.7rem; font-style: italic;">
                                                    <strong>Note:</strong> <?php echo htmlspecialchars($c['notes']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="pe-4 py-4 text-end col-actions">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold border-0 btn-optimise" 
                                                    data-id-offre="<?php echo $c['id_offre']; ?>" 
                                                    data-titre="<?php echo htmlspecialchars($c['offre_titre']); ?>">
                                                <i class="bi bi-magic me-1"></i> Optimiser
                                            </button>
                                            <a href="messagerie.php?id=<?php echo $c['candidature_id']; ?>" class="btn btn-info btn-sm rounded-pill px-3 fw-bold border-0 text-white" title="Discuter avec le recruteur">
                                                <i class="bi bi-chat-dots-fill"></i>
                                            </a>
                                            <?php if ($c['statut'] === 'en_attente'): ?>
                                                <button class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-bold btn-delete" 
                                                        data-id="<?php echo $c['candidature_id']; ?>" 
                                                        title="Retirer ma candidature">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire caché pour la suppression -->
<form id="form-delete-candidature" method="POST" action="supprimer_candidature.php" style="display:none;">
    <input type="hidden" name="id_candidature" id="input-delete-id">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
</form>

<!-- Modal Optimisation IA -->
<div class="modal fade" id="modalOptimise" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold d-flex align-items-center gap-2">
                    <i class="bi bi-magic text-primary"></i> 
                    Optimisation de CV pour : <span id="titreOffreIA" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="stepUpload">
                    <p class="text-muted">Téléchargez votre CV (PDF) pour recevoir des conseils personnalisés de notre IA Gemini afin d'améliorer vos chances pour ce poste.</p>
                    <div class="mb-3">
                        <input type="file" id="fileCV" class="form-control" accept=".pdf">
                    </div>
                    <button id="btnLancerAnalyse" class="btn btn-primary w-100 fw-bold rounded-pill">Lancer l'analyse intelligente</button>
                </div>

                <div id="loaderOptimise" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-3 text-muted">Analyse en cours par l'IA...</p>
                </div>

                <div id="resultOptimise" class="d-none">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="p-3 rounded-4 bg-light text-center border">
                                <div class="small fw-bold text-muted text-uppercase">Score Estimé</div>
                                <div id="scoreEstimeIA" class="display-5 fw-black text-primary">0%</div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="p-3 rounded-4 bg-primary bg-opacity-5 h-100 border border-primary border-opacity-10">
                                <h6 class="fw-bold text-primary small text-uppercase">Verdict Flash</h6>
                                <p id="verdictIA" class="mb-0 small italic"></p>
                            </div>
                        </div>
                        <div class="col-12">
                            <h6 class="fw-bold"><i class="bi bi-lightbulb text-warning"></i> Suggestions d'amélioration</h6>
                            <ul id="suggestionsIA" class="small mb-3"></ul>
                        </div>
                        <div class="col-12">
                            <h6 class="fw-bold"><i class="bi bi-tags text-info"></i> Mots-clés à mettre en avant</h6>
                            <div id="motsClesIA" class="d-flex flex-wrap gap-2"></div>
                        </div>
                        <div class="col-12 mt-3">
                            <div class="p-3 bg-light rounded-4 border">
                                <h6 class="fw-bold small text-uppercase text-muted">Conseil Motivation</h6>
                                <p id="motivationIA" class="mb-0 small"></p>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-outline-secondary w-100 mt-4 rounded-pill fw-bold" onclick="resetIA()">Nouvelle analyse</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Suppression de candidature (Délégation d'événements)
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-delete');
    if (!btn) return;
    
    e.preventDefault();
    const id = btn.getAttribute('data-id');
    
    if (confirm("Êtes-vous sûr de vouloir retirer votre candidature ? Cette action supprimera définitivement votre dossier pour ce poste.")) {
        const inputId = document.getElementById('input-delete-id');
        const form = document.getElementById('form-delete-candidature');
        
        if (inputId && form) {
            inputId.value = id;
            form.submit();
        }
    }
});

let currentOffreId = 0;
const modalOptimise = new bootstrap.Modal(document.getElementById('modalOptimise'));

document.querySelectorAll('.btn-optimise').forEach(btn => {
    btn.addEventListener('click', function() {
        currentOffreId = this.getAttribute('data-id-offre');
        document.getElementById('titreOffreIA').textContent = this.getAttribute('data-titre');
        resetIA();
        modalOptimise.show();
    });
});

function resetIA() {
    document.getElementById('stepUpload').classList.remove('d-none');
    document.getElementById('resultOptimise').classList.add('d-none');
    document.getElementById('loaderOptimise').classList.add('d-none');
    document.getElementById('fileCV').value = '';
}

document.getElementById('btnLancerAnalyse').addEventListener('click', function() {
    const file = document.getElementById('fileCV').files[0];
    if (!file) { alert("Veuillez choisir un fichier PDF."); return; }
    
    document.getElementById('stepUpload').classList.add('d-none');
    document.getElementById('loaderOptimise').classList.remove('d-none');

    const formData = new FormData();
    formData.append('cv', file);
    formData.append('id_offre', currentOffreId);

    fetch('optimiser_cv_ajax.php', {
        method: 'POST',
        body: formData,
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loaderOptimise').classList.add('d-none');
        if (data.error) {
            alert(data.error);
            document.getElementById('stepUpload').classList.remove('d-none');
            return;
        }

        const res = data.suggestions;
        document.getElementById('scoreEstimeIA').textContent = res.score_estime + '%';
        document.getElementById('verdictIA').textContent = res.verdict_flash;
        document.getElementById('motivationIA').textContent = res.commentaire_motivation;

        const suggList = document.getElementById('suggestionsIA');
        suggList.innerHTML = '';
        res.suggestions.forEach(s => { const li = document.createElement('li'); li.textContent = s; suggList.appendChild(li); });

        const keywords = document.getElementById('motsClesIA');
        keywords.innerHTML = '';
        res.mots_cles_manquants.forEach(m => {
            const span = document.createElement('span');
            span.className = 'badge bg-info bg-opacity-10 text-info border border-info-subtle';
            span.textContent = m;
            keywords.appendChild(span);
        });

        document.getElementById('resultOptimise').classList.remove('d-none');
    })
    .catch(err => {
        document.getElementById('loaderOptimise').classList.add('d-none');
        alert("Erreur réseau : " + err.message);
        document.getElementById('stepUpload').classList.remove('d-none');
    });
});
</script>

<?php 
include_footer();
exit();
?>