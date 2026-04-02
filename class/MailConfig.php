<?php
// ═══════════════════════════════════════════════════
//  Gmail SMTP Configuration for NORECO 1 WMS
// ═══════════════════════════════════════════════════
//  SETUP STEPS:
//  1. Go to your Google Account → Security
//  2. Enable 2-Step Verification (if not already on)
//  3. Go to Security → App Passwords
//  4. Generate an App Password for "Mail"
//  5. Paste the 16-character code into 'password' below
// ═══════════════════════════════════════════════════

return [
    'host'      => 'smtp.gmail.com',
    'port'      => 587,
    'username'  => 'norecowms@gmail.com',   // ← ⚠ Replace with your Gmail address
    'password'  => 'xioxvbngvcarklkq',        // ← Gmail App Password ✓
    'from'      => 'norecowms@gmail.com',   // ← ⚠ Same Gmail address here too
    'from_name' => 'NORECO1 WMS',
];
