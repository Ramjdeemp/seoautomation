class SplMinPriorityQueue extends SplPriorityQueue{
    protected function compare(mixed $priority1, mixed $priority2): int
    {
        return parent::compare($priority2, $priority1);
    }
}