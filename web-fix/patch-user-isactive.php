<?php
/**
 * User::isActive() as method makes Laravel treat $user->isActive as a relationship → 500.
 * CheckLoginApi uses ->isActive == 0. Fix with accessor; remove conflicting method.
 */
$path = '/www/wwwroot/admin-homes/app/Models/User.php';
$c = file_get_contents($path);

$old = <<<'PHP'
    public function isActive()
    {
        if ($this->status == 1) {
            return true;
        }
        return false;
    }
PHP;

$new = <<<'PHP'
    /** Used by CheckLoginApi and other code as $user->isActive (0 or 1). */
    public function getIsActiveAttribute(): int
    {
        return (int) $this->status === 1 ? 1 : 0;
    }
PHP;

if (str_contains($c, 'getIsActiveAttribute')) {
    echo "SKIP: already has accessor\n";
    exit(0);
}

if (! str_contains($c, $old)) {
    echo "FAIL: isActive block not found\n";
    exit(1);
}

$c = str_replace($old, $new, $c);
file_put_contents($path, $c);
echo "OK User.php\n";
passthru('php -l ' . escapeshellarg($path));
