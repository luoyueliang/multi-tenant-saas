<?php

namespace MultiTenantSaas\Services\Workflow;

class WorkflowDefinitionParser
{
    protected array $schema = [
        'required' => ['name', 'nodes'],
        'nodes' => [
            'required' => ['id', 'type'],
            'types' => ['start', 'end', 'condition', 'action', 'wait'],
        ],
    ];

    public function validate(array $definition): bool
    {
        foreach ($this->schema['required'] as $field) {
            if (!isset($definition[$field])) {
                return false;
            }
        }
        if (!is_array($definition['nodes']) || empty($definition['nodes'])) {
            return false;
        }
        $hasStart = false;
        $hasEnd = false;
        foreach ($definition['nodes'] as $node) {
            foreach ($this->schema['nodes']['required'] as $field) {
                if (!isset($node[$field])) {
                    return false;
                }
            }
            if (!in_array($node['type'], $this->schema['nodes']['types'])) {
                return false;
            }
            if ($node['type'] === 'start') $hasStart = true;
            if ($node['type'] === 'end') $hasEnd = true;
        }
        return $hasStart && $hasEnd;
    }

    public function parse(array $definition): array
    {
        if (!$this->validate($definition)) {
            throw new \InvalidArgumentException('Invalid workflow definition');
        }
        return [
            'name' => $definition['name'],
            'type' => $definition['type'] ?? 'sequential',
            'config' => $definition['config'] ?? null,
            'nodes' => array_map(fn($n) => [
                'name' => $n['id'],
                'type' => $n['type'],
                'config' => $n['config'] ?? null,
                'order' => $n['order'] ?? 0,
            ], $definition['nodes']),
        ];
    }
}
