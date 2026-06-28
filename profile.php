<?php
require_once __DIR__ . '/config.php';
$pageTitle = 'My Profile';
requireLogin();
require_once __DIR__ . '/includes/header.php';

$user = getCurrentUser();
if (!$user) {
    setFlash('error', 'User not found. Please login again.');
    redirect(SITE_URL . '/login.php');
}
ensureProfileColumns();
$tab = $_GET['tab'] ?? 'profile';
$profileComplete = isProfileComplete($user['id']);
$allowedRoles = ['user', 'developer', 'admin', 'super_admin'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);

    if (empty($name) || empty($email)) {
        setFlash('error', 'Name and email are required.');
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->execute([$email, $user['id']]);
            if ($check->fetch()) {
                setFlash('error', 'Email already in use.');
            } else {
                $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?")->execute([$name, $email, $user['id']]);
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                setFlash('success', 'Profile updated successfully.');
                redirect(SITE_URL . '/profile.php');
            }
        } catch (Exception $e) {
            setFlash('error', 'Update failed: ' . $e->getMessage());
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        setFlash('error', 'All fields are required.');
    } elseif (!password_verify($currentPassword, $user['password'])) {
        setFlash('error', 'Current password is incorrect.');
    } elseif (strlen($newPassword) < 6) {
        setFlash('error', 'New password must be at least 6 characters.');
    } elseif ($newPassword !== $confirmPassword) {
        setFlash('error', 'New passwords do not match.');
    } else {
        try {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashedPassword, $user['id']]);
            setFlash('success', 'Password changed successfully.');
            redirect(SITE_URL . '/profile.php?tab=password');
        } catch (Exception $e) {
            setFlash('error', 'Password update failed: ' . $e->getMessage());
        }
    }
}

// Handle profile completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_profile'])) {
    $fatherName = sanitize($_POST['father_name']);
    $mobile = sanitize($_POST['mobile']);
    $address = sanitize($_POST['address']);
    $dob = sanitize($_POST['dob']);
    $gender = sanitize($_POST['gender']);
    $nationality = sanitize($_POST['nationality']);
    $occupation = sanitize($_POST['occupation']);

    $errors = [];
    if (empty($fatherName)) $errors[] = 'Father name is required.';
    if (empty($mobile)) $errors[] = 'Mobile number is required.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (empty($dob)) $errors[] = 'Date of birth is required.';
    if (empty($gender)) $errors[] = 'Gender is required.';
    if (empty($nationality)) $errors[] = 'Nationality is required.';
    if (empty($occupation)) $errors[] = 'Occupation is required.';

    if (empty($errors)) {
        try {
            ensureProfileColumns();
            $regNo = ensureRegistrationNo($user['id']);

            $pdo->prepare("UPDATE users SET father_name = ?, mobile = ?, address = ?, dob = ?, gender = ?, nationality = ?, occupation = ?, profile_status = 'complete', profile_completed_at = NOW() WHERE id = ?")
                ->execute([$fatherName, $mobile, $address, $dob, $gender, $nationality, $occupation, $user['id']]);

            // Handle ID proof upload
            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['id_proof'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
                if (in_array($ext, $allowedExts)) {
                    $newName = 'proof_' . $user['id'] . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], ID_PROOF_DIR . $newName)) {
                        $pdo->prepare("UPDATE users SET id_proof_file = ? WHERE id = ?")->execute([$newName, $user['id']]);
                    }
                }
            }

            setFlash('success', 'Profile completed successfully! You can now download your registration slip.');
        } catch (Exception $e) {
            setFlash('error', 'Profile update failed: ' . $e->getMessage());
        }
        redirect(SITE_URL . '/profile.php?tab=profile');
    } else {
        setFlash('error', implode(' ', $errors));
    }
}

// Handle profile details update (edit after complete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile_details'])) {
    $fatherName = sanitize($_POST['father_name']);
    $mobile = sanitize($_POST['mobile']);
    $address = sanitize($_POST['address']);
    $dob = sanitize($_POST['dob']);
    $gender = sanitize($_POST['gender']);
    $nationality = sanitize($_POST['nationality']);
    $occupation = sanitize($_POST['occupation']);

    $errors = [];
    if (empty($fatherName)) $errors[] = 'Father name is required.';
    if (empty($mobile)) $errors[] = 'Mobile number is required.';
    if (empty($address)) $errors[] = 'Address is required.';
    if (empty($dob)) $errors[] = 'Date of birth is required.';
    if (empty($gender)) $errors[] = 'Gender is required.';
    if (empty($nationality)) $errors[] = 'Nationality is required.';
    if (empty($occupation)) $errors[] = 'Occupation is required.';

    if (empty($errors)) {
        try {
            ensureProfileColumns();
            $pdo->prepare("UPDATE users SET father_name = ?, mobile = ?, address = ?, dob = ?, gender = ?, nationality = ?, occupation = ? WHERE id = ?")
                ->execute([$fatherName, $mobile, $address, $dob, $gender, $nationality, $occupation, $user['id']]);

            if (isset($_FILES['id_proof']) && $_FILES['id_proof']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['id_proof'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
                if (in_array($ext, $allowedExts)) {
                    $newName = 'proof_' . $user['id'] . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], ID_PROOF_DIR . $newName)) {
                        $pdo->prepare("UPDATE users SET id_proof_file = ? WHERE id = ?")->execute([$newName, $user['id']]);
                    }
                }
            }

            $_SESSION['user_name'] = $user['name'];
            setFlash('success', 'Profile details updated successfully!');
        } catch (Exception $e) {
            setFlash('error', 'Profile update failed: ' . $e->getMessage());
        }
        redirect(SITE_URL . '/profile.php?tab=profile');
    } else {
        setFlash('error', implode(' ', $errors));
    }
}

// Get user orders
$orders = getUserOrders($user['id']);
$downloads = getUserDownloads($user['id']);
$tickets = getUserTickets($user['id']);

// Handle ticket reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_reply'])) {
    $ticketId = (int)$_POST['ticket_id'];
    $message = sanitize($_POST['reply_message']);

    if (!empty($message)) {
        $stmt = $pdo->prepare("SELECT id FROM tickets WHERE id = ? AND user_id = ?");
        $stmt->execute([$ticketId, $user['id']]);
        if ($stmt->fetch()) {
            addTicketReply($ticketId, $user['id'], $message, false);
            $pdo->prepare("UPDATE tickets SET status = 'open', updated_at = NOW() WHERE id = ?")->execute([$ticketId]);
            setFlash('success', 'Reply sent.');
        }
    }
    redirect(SITE_URL . '/profile.php?tab=tickets');
}
?>

<div class="container">
    <div class="section">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h2><?php echo sanitize($user['name']); ?></h2>
                <p><?php echo sanitize($user['email']); ?></p>
                <span class="badge badge-primary"><?php echo ucfirst($user['role']); ?></span>
            </div>
            <div style="display:flex;gap:0.5rem;align-self:flex-start;flex-wrap:wrap">
                <a href="<?php echo SITE_URL; ?>/section-select.php" class="btn btn-outline">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Switch Section
                </a>
                <?php if (in_array($user['role'], ['admin', 'super_admin', 'developer'])): ?>
                <a href="<?php echo ADMIN_URL; ?>/index.php" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                    Go to Dashboard
                </a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/logout.php" class="btn btn-danger" onclick="showConfirm('Are you sure you want to logout?',this.href);return false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Logout
                </a>
            </div>
        </div>

        <!-- Tabs -->
        <div class="filters" style="border-bottom:1px solid var(--border-color);padding-bottom:1rem;margin-bottom:2rem">
            <a href="?tab=profile" class="btn <?php echo $tab === 'profile' ? 'btn-primary' : 'btn-secondary'; ?>">Profile</a>
            <a href="?tab=password" class="btn <?php echo $tab === 'password' ? 'btn-primary' : 'btn-secondary'; ?>">Password</a>
            <a href="?tab=orders" class="btn <?php echo $tab === 'orders' ? 'btn-primary' : 'btn-secondary'; ?>">Orders</a>
            <a href="?tab=downloads" class="btn <?php echo $tab === 'downloads' ? 'btn-primary' : 'btn-secondary'; ?>">Downloads</a>
            <a href="?tab=tickets" class="btn <?php echo $tab === 'tickets' ? 'btn-primary' : 'btn-secondary'; ?>">
                Support Tickets
                <?php
                $openTickets = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ? AND status != 'closed'");
                $openTickets->execute([$user['id']]);
                $openCount = (int)$openTickets->fetchColumn();
                if ($openCount > 0):
                ?>
                <span class="badge" style="margin-left:4px;background:var(--accent);color:white;font-size:0.7rem;padding:2px 6px;border-radius:10px"><?php echo $openCount; ?></span>
                <?php endif; ?>
            </a>
        </div>

        <!-- Profile Tab -->
        <?php if ($tab === 'profile'): ?>

        <?php if (!$profileComplete): ?>
        <div class="alert alert-warning" style="display:flex;align-items:center;gap:1rem;padding:1rem 1.5rem;background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.2);border-radius:var(--border-radius);margin-bottom:1.5rem">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            <div>
                <strong>Complete Your Profile!</strong>
                <p class="mb-0" style="font-size:0.9rem">Please fill in all your details to complete your profile and access the registration slip.</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header d-flex justify-between align-center flex-wrap gap-1">
                <h3 class="mb-0">Basic Information</h3>
                <div class="d-flex gap-1 flex-wrap">
                    <?php if ($profileComplete): ?>
                    <a href="<?php echo SITE_URL; ?>/pdf/registration-slip.php" class="btn btn-sm btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                        Registration Slip
                    </a>
                    <?php endif; ?>
                    <span class="badge badge-<?php echo $profileComplete ? 'success' : 'warning'; ?>" style="align-self:center">
                        Profile: <?php echo $profileComplete ? 'Complete' : 'Incomplete'; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo sanitize($user['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo sanitize($user['email']); ?>" required>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Complete Profile Section -->
        <div class="card">
            <div class="card-header">
                <h3><?php echo $profileComplete ? 'Profile Details' : 'Complete Profile'; ?></h3>
                <?php if ($profileComplete): ?>
                <span class="badge badge-success">Completed</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row" style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
                        <div class="form-group">
                            <label for="father_name">Father Name *</label>
                            <input type="text" id="father_name" name="father_name" class="form-control" value="<?php echo sanitize($user['father_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mobile">Mobile Number *</label>
                            <input type="text" id="mobile" name="mobile" class="form-control" value="<?php echo sanitize($user['mobile'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group" style="grid-column:1/-1">
                            <label for="address">Address *</label>
                            <textarea id="address" name="address" class="form-control" rows="2" required><?php echo sanitize($user['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="dob">Date of Birth *</label>
                            <input type="date" id="dob" name="dob" class="form-control" value="<?php echo $user['dob'] ?? ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nationality">Nationality *</label>
                            <select id="nationality" name="nationality" class="form-control" required>
                                <option value="">Select Nationality</option>
                                <option value="Afghan" <?php echo ($user['nationality'] ?? '') === 'Afghan' ? 'selected' : ''; ?>>Afghan</option>
                                <option value="Albanian" <?php echo ($user['nationality'] ?? '') === 'Albanian' ? 'selected' : ''; ?>>Albanian</option>
                                <option value="Algerian" <?php echo ($user['nationality'] ?? '') === 'Algerian' ? 'selected' : ''; ?>>Algerian</option>
                                <option value="American" <?php echo ($user['nationality'] ?? '') === 'American' ? 'selected' : ''; ?>>American</option>
                                <option value="Argentinian" <?php echo ($user['nationality'] ?? '') === 'Argentinian' ? 'selected' : ''; ?>>Argentinian</option>
                                <option value="Australian" <?php echo ($user['nationality'] ?? '') === 'Australian' ? 'selected' : ''; ?>>Australian</option>
                                <option value="Austrian" <?php echo ($user['nationality'] ?? '') === 'Austrian' ? 'selected' : ''; ?>>Austrian</option>
                                <option value="Bangladeshi" <?php echo ($user['nationality'] ?? '') === 'Bangladeshi' ? 'selected' : ''; ?>>Bangladeshi</option>
                                <option value="Belgian" <?php echo ($user['nationality'] ?? '') === 'Belgian' ? 'selected' : ''; ?>>Belgian</option>
                                <option value="Brazilian" <?php echo ($user['nationality'] ?? '') === 'Brazilian' ? 'selected' : ''; ?>>Brazilian</option>
                                <option value="British" <?php echo ($user['nationality'] ?? '') === 'British' ? 'selected' : ''; ?>>British</option>
                                <option value="Burmese" <?php echo ($user['nationality'] ?? '') === 'Burmese' ? 'selected' : ''; ?>>Burmese</option>
                                <option value="Cambodian" <?php echo ($user['nationality'] ?? '') === 'Cambodian' ? 'selected' : ''; ?>>Cambodian</option>
                                <option value="Canadian" <?php echo ($user['nationality'] ?? '') === 'Canadian' ? 'selected' : ''; ?>>Canadian</option>
                                <option value="Chinese" <?php echo ($user['nationality'] ?? '') === 'Chinese' ? 'selected' : ''; ?>>Chinese</option>
                                <option value="Colombian" <?php echo ($user['nationality'] ?? '') === 'Colombian' ? 'selected' : ''; ?>>Colombian</option>
                                <option value="Cuban" <?php echo ($user['nationality'] ?? '') === 'Cuban' ? 'selected' : ''; ?>>Cuban</option>
                                <option value="Czech" <?php echo ($user['nationality'] ?? '') === 'Czech' ? 'selected' : ''; ?>>Czech</option>
                                <option value="Danish" <?php echo ($user['nationality'] ?? '') === 'Danish' ? 'selected' : ''; ?>>Danish</option>
                                <option value="Dutch" <?php echo ($user['nationality'] ?? '') === 'Dutch' ? 'selected' : ''; ?>>Dutch</option>
                                <option value="Egyptian" <?php echo ($user['nationality'] ?? '') === 'Egyptian' ? 'selected' : ''; ?>>Egyptian</option>
                                <option value="Ethiopian" <?php echo ($user['nationality'] ?? '') === 'Ethiopian' ? 'selected' : ''; ?>>Ethiopian</option>
                                <option value="Filipino" <?php echo ($user['nationality'] ?? '') === 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
                                <option value="Finnish" <?php echo ($user['nationality'] ?? '') === 'Finnish' ? 'selected' : ''; ?>>Finnish</option>
                                <option value="French" <?php echo ($user['nationality'] ?? '') === 'French' ? 'selected' : ''; ?>>French</option>
                                <option value="German" <?php echo ($user['nationality'] ?? '') === 'German' ? 'selected' : ''; ?>>German</option>
                                <option value="Greek" <?php echo ($user['nationality'] ?? '') === 'Greek' ? 'selected' : ''; ?>>Greek</option>
                                <option value="Hungarian" <?php echo ($user['nationality'] ?? '') === 'Hungarian' ? 'selected' : ''; ?>>Hungarian</option>
                                <option value="Icelandic" <?php echo ($user['nationality'] ?? '') === 'Icelandic' ? 'selected' : ''; ?>>Icelandic</option>
                                <option value="Indian" <?php echo ($user['nationality'] ?? '') === 'Indian' ? 'selected' : ''; ?>>Indian</option>
                                <option value="Indonesian" <?php echo ($user['nationality'] ?? '') === 'Indonesian' ? 'selected' : ''; ?>>Indonesian</option>
                                <option value="Iranian" <?php echo ($user['nationality'] ?? '') === 'Iranian' ? 'selected' : ''; ?>>Iranian</option>
                                <option value="Iraqi" <?php echo ($user['nationality'] ?? '') === 'Iraqi' ? 'selected' : ''; ?>>Iraqi</option>
                                <option value="Irish" <?php echo ($user['nationality'] ?? '') === 'Irish' ? 'selected' : ''; ?>>Irish</option>
                                <option value="Israeli" <?php echo ($user['nationality'] ?? '') === 'Israeli' ? 'selected' : ''; ?>>Israeli</option>
                                <option value="Italian" <?php echo ($user['nationality'] ?? '') === 'Italian' ? 'selected' : ''; ?>>Italian</option>
                                <option value="Japanese" <?php echo ($user['nationality'] ?? '') === 'Japanese' ? 'selected' : ''; ?>>Japanese</option>
                                <option value="Jordanian" <?php echo ($user['nationality'] ?? '') === 'Jordanian' ? 'selected' : ''; ?>>Jordanian</option>
                                <option value="Kenyan" <?php echo ($user['nationality'] ?? '') === 'Kenyan' ? 'selected' : ''; ?>>Kenyan</option>
                                <option value="Korean" <?php echo ($user['nationality'] ?? '') === 'Korean' ? 'selected' : ''; ?>>Korean</option>
                                <option value="Kuwaiti" <?php echo ($user['nationality'] ?? '') === 'Kuwaiti' ? 'selected' : ''; ?>>Kuwaiti</option>
                                <option value="Malaysian" <?php echo ($user['nationality'] ?? '') === 'Malaysian' ? 'selected' : ''; ?>>Malaysian</option>
                                <option value="Mexican" <?php echo ($user['nationality'] ?? '') === 'Mexican' ? 'selected' : ''; ?>>Mexican</option>
                                <option value="Moroccan" <?php echo ($user['nationality'] ?? '') === 'Moroccan' ? 'selected' : ''; ?>>Moroccan</option>
                                <option value="Nepalese" <?php echo ($user['nationality'] ?? '') === 'Nepalese' ? 'selected' : ''; ?>>Nepalese</option>
                                <option value="New Zealander" <?php echo ($user['nationality'] ?? '') === 'New Zealander' ? 'selected' : ''; ?>>New Zealander</option>
                                <option value="Nigerian" <?php echo ($user['nationality'] ?? '') === 'Nigerian' ? 'selected' : ''; ?>>Nigerian</option>
                                <option value="Norwegian" <?php echo ($user['nationality'] ?? '') === 'Norwegian' ? 'selected' : ''; ?>>Norwegian</option>
                                <option value="Pakistani" <?php echo ($user['nationality'] ?? '') === 'Pakistani' ? 'selected' : ''; ?>>Pakistani</option>
                                <option value="Palestinian" <?php echo ($user['nationality'] ?? '') === 'Palestinian' ? 'selected' : ''; ?>>Palestinian</option>
                                <option value="Peruvian" <?php echo ($user['nationality'] ?? '') === 'Peruvian' ? 'selected' : ''; ?>>Peruvian</option>
                                <option value="Polish" <?php echo ($user['nationality'] ?? '') === 'Polish' ? 'selected' : ''; ?>>Polish</option>
                                <option value="Portuguese" <?php echo ($user['nationality'] ?? '') === 'Portuguese' ? 'selected' : ''; ?>>Portuguese</option>
                                <option value="Romanian" <?php echo ($user['nationality'] ?? '') === 'Romanian' ? 'selected' : ''; ?>>Romanian</option>
                                <option value="Russian" <?php echo ($user['nationality'] ?? '') === 'Russian' ? 'selected' : ''; ?>>Russian</option>
                                <option value="Saudi" <?php echo ($user['nationality'] ?? '') === 'Saudi' ? 'selected' : ''; ?>>Saudi</option>
                                <option value="Singaporean" <?php echo ($user['nationality'] ?? '') === 'Singaporean' ? 'selected' : ''; ?>>Singaporean</option>
                                <option value="South African" <?php echo ($user['nationality'] ?? '') === 'South African' ? 'selected' : ''; ?>>South African</option>
                                <option value="Spanish" <?php echo ($user['nationality'] ?? '') === 'Spanish' ? 'selected' : ''; ?>>Spanish</option>
                                <option value="Sri Lankan" <?php echo ($user['nationality'] ?? '') === 'Sri Lankan' ? 'selected' : ''; ?>>Sri Lankan</option>
                                <option value="Swedish" <?php echo ($user['nationality'] ?? '') === 'Swedish' ? 'selected' : ''; ?>>Swedish</option>
                                <option value="Swiss" <?php echo ($user['nationality'] ?? '') === 'Swiss' ? 'selected' : ''; ?>>Swiss</option>
                                <option value="Syrian" <?php echo ($user['nationality'] ?? '') === 'Syrian' ? 'selected' : ''; ?>>Syrian</option>
                                <option value="Taiwanese" <?php echo ($user['nationality'] ?? '') === 'Taiwanese' ? 'selected' : ''; ?>>Taiwanese</option>
                                <option value="Thai" <?php echo ($user['nationality'] ?? '') === 'Thai' ? 'selected' : ''; ?>>Thai</option>
                                <option value="Tunisian" <?php echo ($user['nationality'] ?? '') === 'Tunisian' ? 'selected' : ''; ?>>Tunisian</option>
                                <option value="Turkish" <?php echo ($user['nationality'] ?? '') === 'Turkish' ? 'selected' : ''; ?>>Turkish</option>
                                <option value="Ukrainian" <?php echo ($user['nationality'] ?? '') === 'Ukrainian' ? 'selected' : ''; ?>>Ukrainian</option>
                                <option value="Uruguayan" <?php echo ($user['nationality'] ?? '') === 'Uruguayan' ? 'selected' : ''; ?>>Uruguayan</option>
                                <option value="Vietnamese" <?php echo ($user['nationality'] ?? '') === 'Vietnamese' ? 'selected' : ''; ?>>Vietnamese</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="occupation">Occupation *</label>
                            <select id="occupation" name="occupation" class="form-control" required>
                                <option value="">Select Occupation</option>
                                <option value="Accountant" <?php echo ($user['occupation'] ?? '') === 'Accountant' ? 'selected' : ''; ?>>Accountant</option>
                                <option value="Actor" <?php echo ($user['occupation'] ?? '') === 'Actor' ? 'selected' : ''; ?>>Actor</option>
                                <option value="Architect" <?php echo ($user['occupation'] ?? '') === 'Architect' ? 'selected' : ''; ?>>Architect</option>
                                <option value="Artist" <?php echo ($user['occupation'] ?? '') === 'Artist' ? 'selected' : ''; ?>>Artist</option>
                                <option value="Business Owner" <?php echo ($user['occupation'] ?? '') === 'Business Owner' ? 'selected' : ''; ?>>Business Owner</option>
                                <option value="Chef" <?php echo ($user['occupation'] ?? '') === 'Chef' ? 'selected' : ''; ?>>Chef</option>
                                <option value="Civil Servant" <?php echo ($user['occupation'] ?? '') === 'Civil Servant' ? 'selected' : ''; ?>>Civil Servant</option>
                                <option value="Consultant" <?php echo ($user['occupation'] ?? '') === 'Consultant' ? 'selected' : ''; ?>>Consultant</option>
                                <option value="Customer Service" <?php echo ($user['occupation'] ?? '') === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                                <option value="Data Analyst" <?php echo ($user['occupation'] ?? '') === 'Data Analyst' ? 'selected' : ''; ?>>Data Analyst</option>
                                <option value="Data Scientist" <?php echo ($user['occupation'] ?? '') === 'Data Scientist' ? 'selected' : ''; ?>>Data Scientist</option>
                                <option value="Designer" <?php echo ($user['occupation'] ?? '') === 'Designer' ? 'selected' : ''; ?>>Designer</option>
                                <option value="Doctor" <?php echo ($user['occupation'] ?? '') === 'Doctor' ? 'selected' : ''; ?>>Doctor</option>
                                <option value="Driver" <?php echo ($user['occupation'] ?? '') === 'Driver' ? 'selected' : ''; ?>>Driver</option>
                                <option value="Educator" <?php echo ($user['occupation'] ?? '') === 'Educator' ? 'selected' : ''; ?>>Educator</option>
                                <option value="Electrician" <?php echo ($user['occupation'] ?? '') === 'Electrician' ? 'selected' : ''; ?>>Electrician</option>
                                <option value="Engineer" <?php echo ($user['occupation'] ?? '') === 'Engineer' ? 'selected' : ''; ?>>Engineer</option>
                                <option value="Entrepreneur" <?php echo ($user['occupation'] ?? '') === 'Entrepreneur' ? 'selected' : ''; ?>>Entrepreneur</option>
                                <option value="Farmer" <?php echo ($user['occupation'] ?? '') === 'Farmer' ? 'selected' : ''; ?>>Farmer</option>
                                <option value="Freelancer" <?php echo ($user['occupation'] ?? '') === 'Freelancer' ? 'selected' : ''; ?>>Freelancer</option>
                                <option value="Government Employee" <?php echo ($user['occupation'] ?? '') === 'Government Employee' ? 'selected' : ''; ?>>Government Employee</option>
                                <option value="Graphic Designer" <?php echo ($user['occupation'] ?? '') === 'Graphic Designer' ? 'selected' : ''; ?>>Graphic Designer</option>
                                <option value="Health Worker" <?php echo ($user['occupation'] ?? '') === 'Health Worker' ? 'selected' : ''; ?>>Health Worker</option>
                                <option value="Housewife" <?php echo ($user['occupation'] ?? '') === 'Housewife' ? 'selected' : ''; ?>>Housewife</option>
                                <option value="IT Professional" <?php echo ($user['occupation'] ?? '') === 'IT Professional' ? 'selected' : ''; ?>>IT Professional</option>
                                <option value="Journalist" <?php echo ($user['occupation'] ?? '') === 'Journalist' ? 'selected' : ''; ?>>Journalist</option>
                                <option value="Judge" <?php echo ($user['occupation'] ?? '') === 'Judge' ? 'selected' : ''; ?>>Judge</option>
                                <option value="Lawyer" <?php echo ($user['occupation'] ?? '') === 'Lawyer' ? 'selected' : ''; ?>>Lawyer</option>
                                <option value="Manager" <?php echo ($user['occupation'] ?? '') === 'Manager' ? 'selected' : ''; ?>>Manager</option>
                                <option value="Marketing" <?php echo ($user['occupation'] ?? '') === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Mechanic" <?php echo ($user['occupation'] ?? '') === 'Mechanic' ? 'selected' : ''; ?>>Mechanic</option>
                                <option value="Musician" <?php echo ($user['occupation'] ?? '') === 'Musician' ? 'selected' : ''; ?>>Musician</option>
                                <option value="Nurse" <?php echo ($user['occupation'] ?? '') === 'Nurse' ? 'selected' : ''; ?>>Nurse</option>
                                <option value="Other" <?php echo ($user['occupation'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                <option value="Pharmacist" <?php echo ($user['occupation'] ?? '') === 'Pharmacist' ? 'selected' : ''; ?>>Pharmacist</option>
                                <option value="Photographer" <?php echo ($user['occupation'] ?? '') === 'Photographer' ? 'selected' : ''; ?>>Photographer</option>
                                <option value="Plumber" <?php echo ($user['occupation'] ?? '') === 'Plumber' ? 'selected' : ''; ?>>Plumber</option>
                                <option value="Police" <?php echo ($user['occupation'] ?? '') === 'Police' ? 'selected' : ''; ?>>Police</option>
                                <option value="Politician" <?php echo ($user['occupation'] ?? '') === 'Politician' ? 'selected' : ''; ?>>Politician</option>
                                <option value="Private Employee" <?php echo ($user['occupation'] ?? '') === 'Private Employee' ? 'selected' : ''; ?>>Private Employee</option>
                                <option value="Professor" <?php echo ($user['occupation'] ?? '') === 'Professor' ? 'selected' : ''; ?>>Professor</option>
                                <option value="Programmer" <?php echo ($user['occupation'] ?? '') === 'Programmer' ? 'selected' : ''; ?>>Programmer</option>
                                <option value="Real Estate Agent" <?php echo ($user['occupation'] ?? '') === 'Real Estate Agent' ? 'selected' : ''; ?>>Real Estate Agent</option>
                                <option value="Researcher" <?php echo ($user['occupation'] ?? '') === 'Researcher' ? 'selected' : ''; ?>>Researcher</option>
                                <option value="Retired" <?php echo ($user['occupation'] ?? '') === 'Retired' ? 'selected' : ''; ?>>Retired</option>
                                <option value="Salesperson" <?php echo ($user['occupation'] ?? '') === 'Salesperson' ? 'selected' : ''; ?>>Salesperson</option>
                                <option value="Scientist" <?php echo ($user['occupation'] ?? '') === 'Scientist' ? 'selected' : ''; ?>>Scientist</option>
                                <option value="Security Guard" <?php echo ($user['occupation'] ?? '') === 'Security Guard' ? 'selected' : ''; ?>>Security Guard</option>
                                <option value="Software Developer" <?php echo ($user['occupation'] ?? '') === 'Software Developer' ? 'selected' : ''; ?>>Software Developer</option>
                                <option value="Student" <?php echo ($user['occupation'] ?? '') === 'Student' ? 'selected' : ''; ?>>Student</option>
                                <option value="Teacher" <?php echo ($user['occupation'] ?? '') === 'Teacher' ? 'selected' : ''; ?>>Teacher</option>
                                <option value="Technician" <?php echo ($user['occupation'] ?? '') === 'Technician' ? 'selected' : ''; ?>>Technician</option>
                                <option value="Unemployed" <?php echo ($user['occupation'] ?? '') === 'Unemployed' ? 'selected' : ''; ?>>Unemployed</option>
                                <option value="Veterinarian" <?php echo ($user['occupation'] ?? '') === 'Veterinarian' ? 'selected' : ''; ?>>Veterinarian</option>
                                <option value="Warehouse Worker" <?php echo ($user['occupation'] ?? '') === 'Warehouse Worker' ? 'selected' : ''; ?>>Warehouse Worker</option>
                                <option value="Writer" <?php echo ($user['occupation'] ?? '') === 'Writer' ? 'selected' : ''; ?>>Writer</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_proof">ID Proof Upload <?php echo $profileComplete ? '(Uploaded)' : '*'; ?></label>
                            <?php if ($user['id_proof_file']): ?>
                            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.5rem">
                                <span class="badge badge-success">Uploaded</span>
                                <a href="<?php echo SITE_URL; ?>/uploads/id_proofs/<?php echo rawurlencode($user['id_proof_file']); ?>" target="_blank" class="btn btn-sm btn-outline">View</a>
                            </div>
                            <?php endif; ?>
                            <input type="file" id="id_proof" name="id_proof" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf">
                            <p class="text-muted" style="font-size:0.8rem;margin-top:0.25rem">Allowed: JPG, PNG, GIF, WebP, PDF<?php echo $user['id_proof_file'] ? ' (Leave empty to keep current file)' : ''; ?></p>
                        </div>
                    </div>
                    <?php if ($profileComplete): ?>
                    <button type="submit" name="update_profile_details" class="btn btn-primary btn-lg" style="margin-top:0.5rem">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Update Profile
                    </button>
                    <?php else: ?>
                    <button type="submit" name="complete_profile" class="btn btn-success btn-lg" style="margin-top:0.5rem">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Complete Profile
                    </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Password Tab -->
        <?php if ($tab === 'password'): ?>
        <div class="card">
            <div class="card-header">
                <h3>Change Password</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="profile.php?tab=password">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                            <button type="button" class="password-toggle">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="new_password" name="new_password" class="form-control" required autocomplete="new-password">
                            <button type="button" class="password-toggle">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>


                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required autocomplete="new-password">
                            <button type="button" class="password-toggle">
                                <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="icon-eye-off" style="display:none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <div id="passwordMatch" class="password-match-msg" style="display:none;font-size:0.8rem;margin-top:0.25rem"></div>
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Orders Tab -->
        <?php if ($tab === 'orders'): ?>
        <div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
            <h3 class="mb-0">My Orders</h3>
            <div class="d-flex gap-1 flex-wrap">
                <a href="<?php echo SITE_URL; ?>/pdf/export-orders.php" class="btn btn-sm btn-primary" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Orders PDF
                </a>
                <a href="<?php echo SITE_URL; ?>/pdf/export-payments.php" class="btn btn-sm btn-outline" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Payments PDF
                </a>
            </div>
        </div>
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            <h3>No Orders Yet</h3>
            <p>Start shopping to see your orders here.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Browse Products</a>
        </div>
        <?php else: ?>
        <?php foreach ($orders as $order): ?>
        <div class="order-card">
            <div class="order-header">
                <div>
                    <strong><?php echo sanitize($order['order_number']); ?></strong>
                    <span class="text-muted" style="margin-left:0.5rem"><?php echo formatDate($order['created_at']); ?></span>
                </div>
                <span class="badge badge-<?php echo $order['payment_status'] === 'completed' ? 'success' : ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                    <?php echo ucfirst($order['payment_status']); ?>
                </span>
            </div>
            <div class="order-body">
                <p class="mb-2"><?php echo sanitize($order['product_titles'] ?: 'Order #' . $order['order_number']); ?></p>
                <div class="d-flex justify-between align-center">
                    <span class="text-muted">Total: <strong class="text-primary"><?php echo formatPrice($order['final_amount']); ?></strong></span>
                    <a href="<?php echo SITE_URL; ?>/order-details.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline">View Details</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Downloads Tab -->
        <?php if ($tab === 'downloads'): ?>
        <div class="d-flex justify-between align-center mb-3 flex-wrap gap-1">
            <h3 class="mb-0">My Downloads</h3>
            <div class="d-flex gap-1 flex-wrap">
                <a href="<?php echo SITE_URL; ?>/pdf/export-downloads.php" class="btn btn-sm btn-primary" target="_blank">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:4px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                    Downloads PDF
                </a>
            </div>
        </div>
        <?php if (empty($downloads)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <h3>No Downloads</h3>
            <p>Purchased products will appear here with download links.</p>
            <a href="<?php echo SITE_URL; ?>/products.php" class="btn btn-primary">Browse Products</a>
        </div>
        <?php else: ?>
        <?php foreach ($downloads as $download): ?>
        <?php
        $isExpired = $download['expires_at'] && strtotime($download['expires_at']) < time();
        $isLimitReached = $download['download_count'] >= $download['download_limit'];
        $canDownload = !$isExpired && !$isLimitReached;
        $productFiles = getProductFiles($download['product_id']);
        ?>
        <div class="order-card">
            <div class="order-body">
                <div class="d-flex justify-between align-center flex-wrap gap-1">
                    <div>
                        <h4 class="mb-1"><?php echo sanitize($download['title']); ?></h4>
                        <p class="text-muted mb-1">Order: <?php echo sanitize($download['order_number']); ?> | <?php echo formatDate($download['created_at']); ?></p>
                        <p class="text-muted mb-1">Downloads: <?php echo $download['download_count']; ?>/<?php echo $download['download_limit']; ?></p>
                        <?php if ($download['expires_at']): ?>
                        <p class="text-muted mb-1">Expires: <?php echo formatDateTime($download['expires_at']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div>
                        <?php if ($canDownload): ?>
                        <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            Download
                        </a>
                        <?php else: ?>
                        <span class="badge badge-danger"><?php echo $isExpired ? 'Expired' : 'Limit Reached'; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($productFiles) && $canDownload): ?>
                <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid var(--border-color)">
                    <p class="text-muted mb-2" style="font-size:0.8rem;font-weight:600">All Files / Versions:</p>
                    <div style="display:flex;flex-direction:column;gap:0.4rem">
                        <?php foreach ($productFiles as $pf):
                        $hasUrl = !empty($pf['file_url']) && preg_match('#^https?://#', $pf['file_url']);
                        $hasFile = !empty($pf['file_path']);
                        ?>
                        <div style="display:flex;align-items:center;gap:0.5rem;padding:0.4rem 0.6rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm)">
                            <span style="flex:1;font-size:0.85rem">
                                <strong><?php echo sanitize($pf['title']); ?></strong>
                                <?php if ($pf['version']): ?>
                                <span class="badge badge-primary" style="font-size:0.6rem">v<?php echo sanitize($pf['version']); ?></span>
                                <?php endif; ?>
                                <?php if ($pf['file_name']): ?>
                                <span class="text-muted" style="font-size:0.75rem"> — <?php echo sanitize($pf['file_name']); ?> (<?php echo $pf['file_size']; ?>)</span>
                                <?php endif; ?>
                            </span>
                            <?php if ($hasUrl): ?>
                            <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>&file_id=<?php echo $pf['id']; ?>" class="btn btn-sm btn-outline" target="_blank">Download</a>
                            <?php elseif ($hasFile): ?>
                            <a href="<?php echo SITE_URL; ?>/download.php?token=<?php echo $download['download_token']; ?>&file_id=<?php echo $pf['id']; ?>" class="btn btn-sm btn-outline">Download</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Tickets Tab -->
        <?php if ($tab === 'tickets'): ?>
        <?php if (empty($tickets)): ?>
        <div class="empty-state">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            <h3>No Support Tickets</h3>
            <p>Contact us from the Contact page to create a support ticket.</p>
            <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-primary">Contact Us</a>
        </div>
        <?php else: ?>
        <?php foreach ($tickets as $ticket):
            $replies = getTicketReplies($ticket['id']);
        ?>
        <div class="order-card" style="margin-bottom:1rem">
            <div class="order-header">
                <div>
                    <strong><?php echo sanitize($ticket['subject']); ?></strong>
                    <span class="text-muted" style="margin-left:0.5rem;font-size:0.85rem"><?php echo formatDate($ticket['created_at']); ?></span>
                </div>
                <span class="badge badge-<?php echo $ticket['status'] === 'open' ? 'success' : ($ticket['status'] === 'in_progress' ? 'warning' : 'info'); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                </span>
            </div>
            <div class="order-body">
                <div style="padding:0.75rem 1rem;background:var(--bg-tertiary);border-radius:var(--border-radius-sm);margin-bottom:1rem">
                    <p class="mb-1" style="font-size:0.8rem;color:var(--text-muted)"><?php echo formatDateTime($ticket['created_at']); ?></p>
                    <p class="mb-0"><?php echo nl2br(sanitize($ticket['message'])); ?></p>
                </div>

                <?php if (!empty($replies)): ?>
                <?php foreach ($replies as $reply): ?>
                <div style="padding:0.75rem 1rem;background:<?php echo $reply['is_admin'] ? 'rgba(99,102,241,0.05)' : 'var(--bg-tertiary)'; ?>;border-radius:var(--border-radius-sm);border-left:3px solid <?php echo $reply['is_admin'] ? 'var(--primary)' : 'var(--border-color)'; ?>;margin-bottom:0.75rem">
                    <p class="mb-1" style="font-size:0.8rem">
                        <strong><?php echo sanitize($reply['name']); ?></strong>
                        <?php if ($reply['is_admin']): ?><span class="badge badge-primary" style="font-size:0.65rem;padding:2px 6px">Admin</span><?php endif; ?>
                        <span class="text-muted ml-1"><?php echo formatDateTime($reply['created_at']); ?></span>
                    </p>
                    <p class="mb-0"><?php echo nl2br(sanitize($reply['message'])); ?></p>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <?php if ($ticket['status'] !== 'closed'): ?>
                <form method="POST" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--border-color)">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                    <div class="form-group">
                        <label>Your Reply</label>
                        <textarea name="reply_message" class="form-control" rows="3" placeholder="Type your reply..." required></textarea>
                    </div>
                    <button type="submit" name="ticket_reply" class="btn btn-primary btn-sm">Send Reply</button>
                </form>
                <?php else: ?>
                <p class="text-muted mt-2" style="font-size:0.85rem"><em>This ticket is closed.</em></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
