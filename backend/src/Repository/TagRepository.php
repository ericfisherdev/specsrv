<?php

namespace App\Repository;

use App\Entity\Tag;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Tag>
 */
class TagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * Find tags by workspace with optional search term
     */
    public function findByWorkspace(Project $workspace, ?string $search = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('t.usageCount', 'DESC')
            ->addOrderBy('t.name', 'ASC');

        if ($search) {
            $qb->andWhere('LOWER(t.name) LIKE LOWER(:search) OR LOWER(t.description) LIKE LOWER(:search)')
                ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find root tags (tags without parent) by workspace
     */
    public function findRootTagsByWorkspace(Project $workspace): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->andWhere('t.parent IS NULL')
            ->setParameter('workspace', $workspace)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tags by parent
     */
    public function findByParent(Tag $parent): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find most used tags in workspace
     */
    public function findMostUsed(Project $workspace, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('t.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find recently created tags in workspace
     */
    public function findRecentlyCreated(Project $workspace, int $limit = 10): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->setParameter('workspace', $workspace)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find tag by name in workspace (case-insensitive)
     */
    public function findByNameInWorkspace(string $name, Project $workspace): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->andWhere('LOWER(t.name) = LOWER(:name)')
            ->setParameter('workspace', $workspace)
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find tags by alias in workspace
     */
    public function findByAlias(string $alias, Project $workspace): ?Tag
    {
        return $this->createQueryBuilder('t')
            ->join('t.aliases', 'a')
            ->where('a.workspace = :workspace')
            ->andWhere('t.workspace = :workspace')
            ->andWhere('LOWER(a.alias) = LOWER(:alias)')
            ->setParameter('workspace', $workspace)
            ->setParameter('alias', $alias)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Search tags by name or alias
     */
    public function searchByNameOrAlias(string $term, Project $workspace): array
    {
        return $this->createQueryBuilder('t')
            ->leftJoin('t.aliases', 'a')
            ->where('t.workspace = :workspace')
            ->andWhere('LOWER(t.name) LIKE LOWER(:term) OR LOWER(a.alias) LIKE LOWER(:term)')
            ->setParameter('workspace', $workspace)
            ->setParameter('term', '%' . $term . '%')
            ->distinct()
            ->orderBy('t.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get tag hierarchy as a tree structure
     */
    public function getTagTree(Project $workspace): array
    {
        $rootTags = $this->findRootTagsByWorkspace($workspace);
        $tree = [];

        foreach ($rootTags as $rootTag) {
            $tree[] = $this->buildTagNode($rootTag);
        }

        return $tree;
    }

    /**
     * Build tag node with children
     */
    private function buildTagNode(Tag $tag): array
    {
        $node = [
            'id' => $tag->getId(),
            'name' => $tag->getName(),
            'color' => $tag->getColor(),
            'icon' => $tag->getIcon(),
            'description' => $tag->getDescription(),
            'usageCount' => $tag->getUsageCount(),
            'children' => []
        ];

        foreach ($tag->getChildren() as $child) {
            $node['children'][] = $this->buildTagNode($child);
        }

        return $node;
    }

    /**
     * Find tags that can be merged (similar names)
     */
    public function findSimilarTags(Tag $tag): array
    {
        $workspace = $tag->getWorkspace();
        $name = $tag->getName();

        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->andWhere('t.id != :tagId')
            ->andWhere('(
                LOWER(t.name) LIKE LOWER(:similarName1) OR
                LOWER(t.name) LIKE LOWER(:similarName2) OR
                LOWER(t.name) LIKE LOWER(:similarName3)
            )')
            ->setParameter('workspace', $workspace)
            ->setParameter('tagId', $tag->getId())
            ->setParameter('similarName1', '%' . $name . '%')
            ->setParameter('similarName2', $name . '%')
            ->setParameter('similarName3', '%' . $name)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get tags with low usage for cleanup suggestions
     */
    public function findUnusedTags(Project $workspace, int $threshold = 0): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.workspace = :workspace')
            ->andWhere('t.usageCount <= :threshold')
            ->setParameter('workspace', $workspace)
            ->setParameter('threshold', $threshold)
            ->orderBy('t.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Update usage count for a tag
     */
    public function updateUsageCount(Tag $tag): void
    {
        $taskCount = count($tag->getTasks());
        $projectCount = count($tag->getProjects());
        $fileCount = count($tag->getFiles());
        
        $totalUsage = $taskCount + $projectCount + $fileCount;
        $tag->setUsageCount($totalUsage);
        
        $this->getEntityManager()->persist($tag);
        $this->getEntityManager()->flush();
    }

    /**
     * Merge source tag into target tag
     */
    public function mergeTags(Tag $source, Tag $target): void
    {
        $em = $this->getEntityManager();
        
        // Validate workspaces match
        if ($source->getWorkspace()?->getId() !== $target->getWorkspace()?->getId()) {
            throw new \InvalidArgumentException('Cannot merge tags across different workspaces.');
        }
        
        // Prevent merging into self or descendant
        if ($target->isDescendantOf($source) || $target->getId() === $source->getId()) {
            throw new \InvalidArgumentException('Cannot merge into self or descendant.');
        }
        
        // Use transaction for atomicity
        $em->getConnection()->transactional(function () use ($em, $source, $target): void {
            // Move all tasks (clone to avoid modification during iteration)
            foreach (clone $source->getTasks() as $task) {
                if (!$target->getTasks()->contains($task)) {
                    $target->addTask($task);
                }
                $source->removeTask($task);
            }
            
            // Move all projects (clone to avoid modification during iteration)
            foreach (clone $source->getProjects() as $project) {
                if (!$target->getProjects()->contains($project)) {
                    $target->addProject($project);
                }
                $source->removeProject($project);
            }
            
            // Move all files (clone to avoid modification during iteration)
            foreach (clone $source->getFiles() as $file) {
                if (!$target->getFiles()->contains($file)) {
                    $target->addFile($file);
                }
                $source->removeFile($file);
            }
            
            // Move children (clone to avoid modification during iteration)
            foreach (clone $source->getChildren() as $child) {
                $child->setParent($target);
            }
            
            // Move aliases with de-duplication (case-insensitive)
            $existingAliases = [];
            foreach ($target->getAliases() as $alias) {
                $aliasName = $alias->getAlias();
                if ($aliasName !== null) {
                    $existingAliases[mb_strtolower($aliasName)] = true;
                }
            }
            
            foreach (clone $source->getAliases() as $alias) {
                $aliasName = $alias->getAlias();
                if ($aliasName === null) {
                    continue;
                }
                $normalizedAlias = mb_strtolower($aliasName);
                if (!isset($existingAliases[$normalizedAlias])) {
                    // Alias doesn't exist on target, move it
                    $alias->setTag($target);
                    $target->addAlias($alias);
                    $existingAliases[$normalizedAlias] = true;
                } else {
                    // Duplicate alias, remove it
                    $source->removeAlias($alias);
                    $em->remove($alias);
                }
            }
            
            // Note: If using database triggers for usage counts, this call can be removed
            // to avoid drift between application and database counts
            $this->updateUsageCount($target);
            
            // Remove source tag
            $em->remove($source);
            $em->flush();
        });
    }
}