<?php 
$pageTitle = 'Mon profil';
ob_start();
?>

<div class="max-w-2xl mx-auto">
    <div class="card">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Mon profil</h2>
        </div>
        <div class="card-body">
            <form 
                x-data="{
                    prenom: '<?= htmlspecialchars($_SESSION['user_prenom'] ?? '') ?>',
                    nom: '<?= htmlspecialchars($_SESSION['user_nom'] ?? '') ?>',
                    telephone: '<?= htmlspecialchars($_SESSION['user_telephone'] ?? '') ?>',
                    username: '<?= htmlspecialchars($_SESSION['user_username'] ?? '') ?>',
                    loading: false
                }"
                @submit.prevent="async () => {
                    loading = true;
                    try {
                        await App.api('/api/auth/profile', 'POST', {
                            prenom: prenom,
                            nom: nom,
                            telephone: telephone
                        });
                        
                        // Mettre à jour la session localement
                        <?php 
                        $_SESSION['user_prenom'] = $user['prenom'] ?? $_SESSION['user_prenom'];
                        $_SESSION['user_nom'] = $user['nom'] ?? $_SESSION['user_nom'];
                        $_SESSION['user_telephone'] = $user['telephone'] ?? $_SESSION['user_telephone'];
                        ?>
                        
                        App.notify('Profil mis à jour avec succès');
                        window.location.reload();
                    } catch (e) {
                        App.notify(e.message, 'error');
                    } finally {
                        loading = false;
                    }
                }"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Prénom</label>
                        <input type="text" x-model="prenom" class="input">
                    </div>
                    <div>
                        <label class="label">Nom</label>
                        <input type="text" x-model="nom" class="input">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label class="label">Nom d'utilisateur</label>
                    <input type="text" x-model="username" class="input bg-gray-50" readonly>
                    <p class="text-xs text-gray-500 mt-1">Le nom d'utilisateur ne peut pas être modifié</p>
                </div>
                
                <div class="mt-4">
                    <label class="label">Téléphone</label>
                    <input type="tel" x-model="telephone" class="input">
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn-primary" :disabled="loading">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Changement de mot de passe -->
    <div class="card mt-6">
        <div class="card-header">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Changer le mot de passe</h2>
        </div>
        <div class="card-body">
            <form 
                x-data="{
                    current_password: '',
                    new_password: '',
                    new_password_confirmation: '',
                    loading: false
                }"
                @submit.prevent="async () => {
                    if (new_password !== new_password_confirmation) {
                        App.notify('Les mots de passe ne correspondent pas', 'error');
                        return;
                    }
                    
                    if (new_password.length < 6) {
                        App.notify('Le mot de passe doit contenir au moins 6 caractères', 'error');
                        return;
                    }
                    
                    loading = true;
                    try {
                        await App.api('/api/auth/password', 'POST', {
                            current_password: current_password,
                            new_password: new_password
                        });
                        
                        App.notify('Mot de passe changé avec succès');
                        current_password = '';
                        new_password = '';
                        new_password_confirmation = '';
                    } catch (e) {
                        App.notify(e.message, 'error');
                    } finally {
                        loading = false;
                    }
                }"
            >
                <div>
                    <label class="label">Mot de passe actuel</label>
                    <input type="password" x-model="current_password" class="input" required>
                </div>
                
                <div class="mt-4">
                    <label class="label">Nouveau mot de passe</label>
                    <input type="password" x-model="new_password" class="input" required minlength="6">
                </div>
                
                <div class="mt-4">
                    <label class="label">Confirmer le nouveau mot de passe</label>
                    <input type="password" x-model="new_password_confirmation" class="input" required>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="submit" class="btn-primary" :disabled="loading">
                        Changer le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
require_once ROOT_PATH . '/app/Views/layouts/app.php';
?>
