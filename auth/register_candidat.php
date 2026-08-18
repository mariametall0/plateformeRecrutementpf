<?php
require_once "../includes/layout.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    verify_csrf_token();

    $input = json_decode(file_get_contents("php://input"), true) ?? [];
    $nom           = trim($input["nom"] ?? $_POST["nom"] ?? "");
    $email         = trim($input["email"] ?? $_POST["email"] ?? "");
    $pays          = trim($input["pays"] ?? $_POST["pays"] ?? "");
    $telephone     = trim($input["telephone"] ?? $_POST["telephone"] ?? "");
    $adresse       = trim($input["adresse"] ?? $_POST["adresse"] ?? "");
    $secteur       = trim($input["secteur"] ?? $_POST["secteur"] ?? "");
    $niveau_etude  = trim($input["niveau_etude"] ?? $_POST["niveau_etude"] ?? "");
    $date_naissance= trim($input["date_naissance"] ?? $_POST["date_naissance"] ?? "");
    $password      = $input["password"] ?? $_POST["password"] ?? "";
    $confirm       = $input["confirm_password"] ?? $_POST["confirm_password"] ?? "";

    if (empty($nom) || empty($email) || empty($password)) {
        send_error("Nom, email et mot de passe requis.");
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        send_error("Format d'email invalide.");
    } elseif (strlen($password) < 8) {
        send_error("Le mot de passe doit contenir au moins 8 caractères.");
    } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        send_error("Le mot de passe doit contenir au moins une lettre et un chiffre.");
    } elseif ($password != $confirm) {
        send_error("Les mots de passe ne correspondent pas.");
    }

    if (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE || $_FILES['cv']['name'] === '') {
        send_error("Le CV est obligatoire.");
    }

    $cv_path = null;
    $result = handle_file_upload($_FILES['cv'], 'cvs');
    if ($result) {
        $cv_path = $result;
    } else {
        if (isset($_SESSION['error_message'])) {
             $msg = $_SESSION['error_message'];
             unset($_SESSION['error_message']);
             send_error($msg);
        } else {
             send_error("Erreur lors du téléchargement du CV.");
        }
    }

    if (!isset($_SESSION['error_message'])) {
        $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            send_error("Cet email existe déjà.", 409);
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs
                    (nom, email, mot_de_passe, telephone, adresse, pays, role, statut)
                    VALUES (?, ?, ?, ?, ?, ?, 'candidat', 'actif')
                ");
                $stmt->execute([$nom, $email, $password_hash, $telephone, $adresse, $pays]);
                $user_id = $pdo->lastInsertId();

                $stmt_profil = $pdo->prepare("
                    INSERT INTO profils_candidats 
                    (id_utilisateur, secteur_specialite, niveau_etude, date_naissance, cv_path)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_profil->execute([
                    $user_id,
                    $secteur,
                    $niveau_etude,
                    $date_naissance ?: null,
                    $cv_path
                ]);

                $pdo->commit();

            if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false) {
                send_json(['success' => true, 'message' => 'Compte créé.'], 201);
            } else {
                $_SESSION["id"]   = $user_id;
                $_SESSION["nom"]  = htmlspecialchars($nom);
                $_SESSION["role"] = 'candidat';
                $_SESSION["email"] = $email;
                $_SESSION["auth_token"] = bin2hex(random_bytes(32));
                
                $redirect = $_SESSION['redirect_after_login'] ?? "../candidat/dashboard.php";
                unset($_SESSION['redirect_after_login']);
                
                $_SESSION['success_message'] = "Bienvenue ! Votre compte a été créé avec succès.";
                header("Location: $redirect");
                exit();
            }
            } catch (Exception $e) {
                $pdo->rollBack();
                send_error("Erreur lors de l'inscription : " . $e->getMessage(), 500);
            }
        }
    }
}

// Affichage du formulaire
include_header("Inscription Candidat");
?>

<div class="register-page-v2">
    <div class="auth-wrapper">
        <div class="card-wrapper">
            <div class="row g-0">
                <!-- Partie Branding (Gauche) -->
                <div class="col-md-4 d-none d-md-flex branding-side flex-column justify-content-between">
                    <div>
                        <a href="../index.php" class="back-link">
                            <i class="bi bi-arrow-left"></i> Retour
                        </a>
                        <div class="branding-content">
                            <div class="branding-icon">
                                <i class="bi bi-person-plus-fill"></i>
                            </div>
                            <h1 class="fw-black mb-3 branding-title">Rejoignez-nous</h1>
                            <p class="branding-text">Démarquez-vous, créez un profil riche et postulez aux meilleures offres en un clic.</p>
                        </div>
                    </div>
                    <div class="branding-copyright">
                        &copy; <?php echo date('Y'); ?> Admissio
                    </div>
                </div>

                <!-- Partie Formulaire (Droite) -->
                <div class="col-md-8">
                    <div class="form-wrapper">
                        <div class="mb-4">
                            <h2 class="fw-bold text-dark mb-1 form-title">Espace Candidat</h2>
                            <p class="text-muted form-subtitle">Complétez ce formulaire pour créer votre compte.</p>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                            <!-- Nom complet -->
                            <div class="input-field">
                                <label class="form-label">Nom complet <span class="required">*</span></label>
                                <div class="input-group form-control-pro">
                                    <i class="bi bi-person"></i>
                                    <input type="text" name="nom" placeholder="Prénom et Nom" required>
                                </div>
                            </div>

                            <div class="row g-3">
                                <!-- Email -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Email <span class="required">*</span></label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-envelope"></i>
                                            <input type="email" name="email" placeholder="nom@exemple.ma" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Pays -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Pays</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-globe"></i>
                                            <select name="pays" id="pays_select"></select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Téléphone -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Téléphone</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-telephone" id="indicatif_display">+222</i>
                                            <input type="tel" name="telephone" id="telephone" placeholder="6XX XXX XXX">
                                        </div>
                                    </div>
                                </div>

                                <!-- Date de naissance -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Date de naissance</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-calendar"></i>
                                            <input type="date" name="date_naissance">
                                        </div>
                                    </div>
                                </div>

                                <!-- Ville -->
                                <div class="col-12">
                                    <div class="input-field">
                                        <label class="form-label">Ville</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-geo-alt"></i>
                                            <select name="adresse" id="ville_select"></select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Secteur / Spécialité -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Secteur / Spécialité</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-briefcase"></i>
                                            <input type="text" name="secteur" placeholder="Ex: Finance, Dev...">
                                        </div>
                                    </div>
                                </div>

                                <!-- Niveau d'étude -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Niveau d'étude</label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-mortarboard"></i>
                                            <select name="niveau_etude">
                                                <option value="">-- Sélectionner votre niveau --</option>
                                                <option value="Bac">Baccalauréat</option>
                                                <option value="BTS">BTS</option>
                                                <option value="Licence">Licence</option>
                                                <option value="Master">Master</option>
                                                <option value="Doctorat">Doctorat</option>
                                                <option value="Diplôme Tech">Diplôme Technique</option>
                                                <option value="Formation Prof">Formation Professionnelle</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- CV -->
                                <div class="col-12">
                                    <div class="input-field">
                                        <label class="form-label">Upload CV (PDF/Docx) <span class="required">*</span></label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                            <input type="file" name="cv" accept=".pdf,.doc,.docx" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Password -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Mot de passe <span class="required">*</span></label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-lock"></i>
                                            <input type="password" name="password" id="password" placeholder="••••••••" required oninput="checkPasswordStrength(this.value)">
                                            <button class="btn border-0 p-0 text-muted" type="button" onclick="togglePassword('password', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                        <div class="progress mt-2" style="height: 5px;">
                                            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small id="password-strength-text" class="text-muted mt-1 d-block" style="font-size: 0.75rem;">Force du mot de passe</small>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div class="col-md-6">
                                    <div class="input-field">
                                        <label class="form-label">Confirmer <span class="required">*</span></label>
                                        <div class="input-group form-control-pro">
                                            <i class="bi bi-check2-all"></i>
                                            <input type="password" name="confirm_password" id="confirm_password" placeholder="••••••••" required>
                                            <button class="btn border-0 p-0 text-muted" type="button" onclick="togglePassword('confirm_password', this)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn-pro btn-pro-primary btn-submit">
                                <i class="bi bi-person-check"></i> Créer mon compte
                            </button>
                        </form>

                        <div class="divider">
                            <hr>
                            <span>Déjà inscrit ?</span>
                            <hr>
                        </div>

                        <div class="text-center">
                            <span class="text-muted small">Connectez-vous à votre compte</span> 
                            <a href="login.php?role=candidat" class="register-link register-link-primary ms-2">Se connecter</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Base de données complète : Pays, Indicatifs, et Villes
const countriesData = [
    { name: "Mauritanie", code: "+222", flag: "🇲🇷", cities: ["Nouakchott", "Nouadhibou", "Kiffa", "Carrefour (Kaedi)", "Atar", "Rosso", "Néma", "Tidjikja", "Akjoujt", "Sélibaby"] },
    { name: "Maroc", code: "+212", flag: "🇲🇦", cities: ["Casablanca", "Rabat", "Fès", "Marrakech", "Tanger", "Agadir", "Meknes", "Oujda", "Safi", "El Jadida"] },
    { name: "Algérie", code: "+213", flag: "🇩🇿", cities: ["Alger", "Oran", "Annaba", "Constantine", "Tlemcen", "Sétif", "Batna", "Blida", "Tizi Ouzou", "Béjaïa"] },
    { name: "Tunisie", code: "+216", flag: "🇹🇳", cities: ["Tunis", "Sfax", "Sousse", "Gabès", "Kairouan", "Gafsa", "Béja", "Bizerte", "Djerba", "Ryadh"] },
    { name: "Égypte", code: "+20", flag: "🇪🇬", cities: ["Le Caire", "Giza", "Alexandrie", "Hélouan", "Tanta", "Mansoura", "Ismaïlia", "Port-Saïd", "Suez", "Assiout"] },
    { name: "Sénégal", code: "+221", flag: "🇸🇳", cities: ["Dakar", "Thiès", "Kaolack", "Tambacounda", "Saint-Louis", "Ziguinchor", "Kolda", "Matam", "Louga", "Rufisque"] },
    { name: "Mali", code: "+223", flag: "🇲🇱", cities: ["Bamako", "Ségou", "Mopti", "Koulikoro", "Kayes", "Gao", "Sikasso", "Kita", "Kangaba", "Tombouctou"] },
    { name: "Burkina Faso", code: "+226", flag: "🇧🇫", cities: ["Ouagadougou", "Bobo-Dioulasso", "Koudougou", "Ouahigouya", "Banfora", "Tenkodogo", "Ziniaré", "Fada N'gourma", "Dori", "Garango"] },
    { name: "Kenya", code: "+254", flag: "🇰🇪", cities: ["Nairobi", "Mombasa", "Kisumu", "Nakuru", "Eldoret", "Thika", "Kampala", "Kericho", "Machakos", "Nyeri"] },
    { name: "Nigeria", code: "+234", flag: "🇳🇬", cities: ["Lagos", "Abuja", "Ibadan", "Kano", "Kaduna", "Port-Harcourt", "Benin-City", "Maiduguri", "Zaria", "Ilorin"] },
    { name: "Ghana", code: "+233", flag: "🇬🇭", cities: ["Accra", "Kumasi", "Tamale", "Sekondi-Takoradi", "Cape Coast", "Tema", "Obuasi", "Koforidua", "Legon", "Ashiaman"] },
    { name: "Cameroun", code: "+237", flag: "🇨🇲", cities: ["Yaoundé", "Douala", "Garoua", "Bamenda", "Buea", "Limbé", "Bafoussam", "Ngaoundéré", "Kumba", "Bertoua"] },
    { name: "Côte d'Ivoire", code: "+225", flag: "🇨🇮", cities: ["Abidjan", "Yamoussoukro", "Gagnoa", "Bouaké", "Daloa", "San-Pédro", "Korhogo", "Dimbokro", "Agboville", "Adzopé"] },
    { name: "France", code: "+33", flag: "🇫🇷", cities: ["Paris", "Marseille", "Lyon", "Toulouse", "Nice", "Nantes", "Bordeaux", "Lille", "Rennes", "Strasbourg"] },
    { name: "Belgique", code: "+32", flag: "🇧🇪", cities: ["Bruxelles", "Anvers", "Gand", "Charleroi", "Liège", "Bruges", "Namur", "Louvain", "Tournai", "Malines"] },
    { name: "Suisse", code: "+41", flag: "🇨🇭", cities: ["Zürich", "Genève", "Bâle", "Berne", "Lausanne", "Lucerne", "St-Gall", "Neuchâtel", "Winterthour", "Schaffhouse"] },
    { name: "Canada", code: "+1", flag: "🇨🇦", cities: ["Toronto", "Vancouver", "Montréal", "Calgary", "Ottawa", "Winnipeg", "Québec", "Hamilton", "Edmonton", "Kitchener"] },
    { name: "États-Unis", code: "+1", flag: "🇺🇸", cities: ["New York", "Los Angeles", "Chicago", "Houston", "Phoenix", "Philadelphie", "San Antonio", "San Diego", "Dallas", "San Jose"] },
    { name: "Royaume-Uni", code: "+44", flag: "🇬🇧", cities: ["Londres", "Manchester", "Birmingham", "Leeds", "Glasgow", "Liverpool", "Newcastle", "Bristol", "Leicester", "York"] },
    { name: "Allemagne", code: "+49", flag: "🇩🇪", cities: ["Berlin", "Munich", "Francfort", "Cologne", "Hambourg", "Dusseldorf", "Dortmund", "Essen", "Stuttgart", "Dresde"] },
    { name: "Espagne", code: "+34", flag: "🇪🇸", cities: ["Madrid", "Barcelone", "Valence", "Séville", "Bilbao", "Malaga", "Murica", "Palma", "Las Palmas", "Alicante"] },
    { name: "Italie", code: "+39", flag: "🇮🇹", cities: ["Rome", "Milan", "Naples", "Turin", "Palerme", "Gênes", "Bologne", "Florence", "Bari", "Catane"] },
    { name: "Portugal", code: "+351", flag: "🇵🇹", cities: ["Lisbonne", "Porto", "Covilhã", "Braga", "Funchal", "Covilhã", "Évora", "Ponta Delgada", "Setúbal", "Viseu"] },
    { name: "Japon", code: "+81", flag: "🇯🇵", cities: ["Tokyo", "Osaka", "Yokohama", "Nagoya", "Kyoto", "Kobe", "Kawasaki", "Saitama", "Hiroshima", "Fukuoka"] },
    { name: "Brésil", code: "+55", flag: "🇧🇷", cities: ["São Paulo", "Rio de Janeiro", "Brasília", "Salvador", "Fortaleza", "Belo Horizonte", "Manaus", "Recife", "Curitiba", "Porto Alegre"] }
];

// Initialiser les listes pays
function initCountrySelects() {
    const paysSelect = document.getElementById('pays_select');
    
    // Remplir le dropdown des pays
    countriesData.sort((a, b) => a.name.localeCompare(b.name)).forEach(country => {
        const option = new Option(`${country.flag} ${country.name}`, country.name);
        paysSelect.add(option);
    });
    
    // Pré-sélectionner Mauritanie
    paysSelect.value = "Mauritanie";
    updateCitiesAndIndicatif("Mauritanie");
}

// Mettre à jour indicatif et villes quand change le pays
function updateCitiesAndIndicatif(countryName, selectedCity = '') {
    const country = countriesData.find(c => c.name === countryName);
    
    if (country) {
        // Mettre à jour l'indicatif
        const indicatifDisplay = document.getElementById('indicatif_display');
        if (indicatifDisplay) {
            indicatifDisplay.textContent = country.code;
        }
        
        // Mettre à jour le téléphone avec le nouvel indicatif
        const telInput = document.getElementById('telephone');
        if (telInput && telInput.value) {
            const numbersOnly = telInput.value.replace(/^\+?\d+\s?/, '').trim();
            telInput.value = country.code + ' ' + numbersOnly;
        }
        
        // Remplir les villes
        const villeSelect = document.getElementById('ville_select');
        villeSelect.innerHTML = '<option value="">-- Sélectionner une ville --</option>';
        
        country.cities.forEach(city => {
            const option = new Option(city, city);
            villeSelect.add(option);
        });
        
        // Pré-sélectionner la ville
        if (selectedCity) {
            villeSelect.value = selectedCity;
        }
    }
}

// Événement changement de pays
document.addEventListener('DOMContentLoaded', function() {
    const paysSelect = document.getElementById('pays_select');
    if (paysSelect) {
        paysSelect.addEventListener('change', function() {
            updateCitiesAndIndicatif(this.value);
        });
    }
    
    initCountrySelects();
});

// Real-time password confirmation check
const passwordInput = document.getElementById('password');
const confirmInput = document.getElementById('confirm_password');

function validatePasswords() {
    if (confirmInput.value === '') {
        confirmInput.closest('.input-group').style.borderColor = '';
        return;
    }

    if (passwordInput.value === confirmInput.value) {
        confirmInput.closest('.input-group').style.borderColor = '#10b981'; // Success green
    } else {
        confirmInput.closest('.input-group').style.borderColor = '#ef4444'; // Error red
    }
}

if (passwordInput && confirmInput) {
    passwordInput.addEventListener('input', validatePasswords);
    confirmInput.addEventListener('input', validatePasswords);
}

// Form validation before submit
const emailInput = document.querySelector('input[name="email"]');
let isEmailAvailable = true;

if (emailInput) {
    emailInput.addEventListener('blur', function() {
        const email = this.value.trim();
        if (email === '') return;
        
        fetch(`check_email_ajax.php?email=${encodeURIComponent(email)}`)
            .then(r => r.json())
            .then(d => {
                if (d.available === false) {
                    isEmailAvailable = false;
                    emailInput.closest('.input-group').style.borderColor = '#ef4444';
                    showAlert("Cet email est déjà utilisé par un autre compte.", "error");
                } else {
                    isEmailAvailable = true;
                    emailInput.closest('.input-group').style.borderColor = '#10b981';
                }
            });
    });
}

document.querySelector('form').addEventListener('submit', function(e) {
    if (!isEmailAvailable) {
        e.preventDefault();
        showAlert("Veuillez utiliser une autre adresse email.", "error");
        emailInput.focus();
        return;
    }
    if (passwordInput.value !== confirmInput.value) {
        e.preventDefault();
        showAlert("Les mots de passe ne correspondent pas.", "error");
        confirmInput.focus();
    }
});

// Toggle password visibility
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    if (field.type === 'password') {
        field.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        field.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

function checkPasswordStrength(password) {
    const bar = document.getElementById('password-strength-bar');
    const text = document.getElementById('password-strength-text');
    let strength = 0;
    
    if (password.length >= 8) strength += 20;
    if (password.match(/[a-z]+/)) strength += 20;
    if (password.match(/[A-Z]+/)) strength += 20;
    if (password.match(/[0-9]+/)) strength += 20;
    if (password.match(/[$@#&!]+/)) strength += 20;
    
    bar.style.width = strength + '%';
    
    if (strength <= 20) {
        bar.className = 'progress-bar bg-danger';
        text.textContent = 'Trés Faible';
    } else if (strength <= 40) {
        bar.className = 'progress-bar bg-warning';
        text.textContent = 'Faible';
    } else if (strength <= 60) {
        bar.className = 'progress-bar bg-info';
        text.textContent = 'Moyen';
    } else if (strength <= 80) {
        bar.className = 'progress-bar bg-success';
        text.textContent = 'Fort';
    } else {
        bar.className = 'progress-bar bg-success';
        text.textContent = 'Très Fort';
    }
}
</script>

<?php 
include_footer();
?>
