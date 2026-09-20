<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$agentDirectory = $root.'/.codex/agents';
$policyPath = $root.'/.codex/agent-capabilities.toml';

if (! is_dir($agentDirectory)) {
    fwrite(STDERR, "Agent directory not found: {$agentDirectory}\n");
    exit(1);
}

$agentFiles = glob($agentDirectory.'/*.toml') ?: [];
$agentNames = [];
$errors = [];

foreach ($agentFiles as $agentFile) {
    $contents = file_get_contents($agentFile);
    if ($contents === false) {
        $errors[] = "Unable to read {$agentFile}";
        continue;
    }

    $name = null;
    if (preg_match('/^name\s*=\s*"([^"]+)"/m', $contents, $matches) === 1) {
        $name = $matches[1];
    }

    if ($name === null) {
        $errors[] = basename($agentFile).': missing name';
        continue;
    }

    if (isset($agentNames[$name])) {
        $errors[] = "duplicate agent name: {$name}";
    }
    $agentNames[$name] = true;

    foreach (['description', 'model', 'model_reasoning_effort', 'sandbox_mode', 'developer_instructions'] as $required) {
        if (! preg_match('/^'.preg_quote($required, '/').'\s*=/m', $contents)) {
            $errors[] = "{$name}: missing {$required}";
        }
    }

    if (! str_contains($contents, 'Return:') && $name !== 'fortechlead-team-orchestrator') {
        $errors[] = "{$name}: missing Return section";
    }
}

if (! is_file($policyPath)) {
    $errors[] = 'missing .codex/agent-capabilities.toml';
} else {
    $policy = file_get_contents($policyPath);
    if ($policy === false || ! str_contains($policy, 'max_correction_cycles = 3')) {
        $errors[] = 'policy: max_correction_cycles must be 3';
    }

    foreach (array_keys($agentNames) as $agentName) {
        if (! str_contains((string) $policy, '[agents.'.$agentName.']')) {
            $errors[] = "policy: missing {$agentName}";
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode("\n", $errors)."\n");
    exit(1);
}

printf("Validated %d Codex agents and their capability policy.\n", count($agentNames));
