<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "includes/db_connection.php";

include "includes/header.php";

?>


    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <div class="row align-items-center">                <div class="col-12 text-center">
                    <h1 class="hero-title">Trouvez votre logement idéal à Omnes</h1>
                    <p class="hero-text">La plateforme exclusive de colocation et de location pour les étudiants et le personnel d'Omnes</p>
                    <div class="d-flex flex-wrap gap-3 justify-content-center mt-4">
                        <a href="pages/search.php" class="btn btn-light btn-lg hero-btn">
                            <i class="fas fa-search me-2"></i>Explorer les logements
                        </a>
                        <a href="pages/publish.php" class="btn btn-outline-light btn-lg hero-btn-outline">
                            <i class="fas fa-home me-2"></i>Proposer mon logement
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="hero-wave">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 100" preserveAspectRatio="none">
                <path fill="#ffffff" fill-opacity="1" d="M0,64L80,69.3C160,75,320,85,480,80C640,75,800,53,960,48C1120,43,1280,53,1360,58.7L1440,64L1440,320L1360,320C1280,320,1120,320,960,320C800,320,640,320,480,320C320,320,160,320,80,320L0,320Z"></path>
            </svg>
        </div>
    </div>

    <div class="container">
        <div class="form-card mb-4">
            <h2 class="mb-4">Trouver un logement</h2>
            <form action="pages/search.php" method="GET">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="location" name="location" placeholder="Lieu">
                            <label for="location">Lieu</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="start_date" name="start_date">
                            <label for="start_date">Date d'arrivée</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-floating mb-3">
                            <input type="date" class="form-control" id="end_date" name="end_date">
                            <label for="end_date">Date de départ</label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-floating mb-3">
                            <select class="form-select" id="guests" name="guests">
                                <option value="1">1 personne</option>
                                <option value="2">2 personnes</option>
                                <option value="3">3 personnes</option>
                                <option value="4">4+ personnes</option>
                            </select>
                            <label for="guests">Nombre de personnes</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">Rechercher</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="row mb-4">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Comment ça fonctionne?</h3>
                        <p class="card-text">OmnesBnB est une plateforme de location de logements réservée aux étudiants et au personnel d'Omnes. Vous pouvez :</p>
                        <ul>
                            <li>Chercher un logement à louer</li>
                            <li>Proposer votre logement à la location ou à la colocation</li>
                            <li>Annoncer quand vous libérez un logement</li>
                        </ul>
                        <p>Pour publier ou réserver un logement, vous devez vous inscrire avec une adresse email Omnes valide.</p>
                        <a href="pages/register.php" class="btn btn-outline-primary">S'inscrire maintenant</a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h3 class="card-title">Pourquoi choisir OmnesBnB?</h3>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>Communauté fermée et sécurisée d'étudiants et de personnel Omnes</div>
                            </li>
                            <li class="list-group-item d-flex">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>Pas de frais cachés - tarifs transparents</div>
                            </li>
                            <li class="list-group-item d-flex">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>Support et assistance par l'équipe d'administration</div>
                            </li>
                            <li class="list-group-item d-flex">
                                <i class="fas fa-check-circle text-success me-2 mt-1"></i>
                                <div>Possibilité de sous-louer pendant vos périodes d'alternance</div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include "includes/footer.php"; ?>