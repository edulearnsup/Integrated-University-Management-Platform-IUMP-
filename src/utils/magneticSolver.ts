import { GraphNodeDto } from '../domain/types';

export const NODE_WIDTH = 220;
export const NODE_HEIGHT = 82;
export const DEFAULT_CLEARANCE = 20;

export interface NodePosition {
  x: number; // center x of the node
  y: number; // top y of the node
}

export interface BoundingBox {
  left: number;
  right: number;
  top: number;
  bottom: number;
  centerX: number;
  centerY: number;
}

export function getNodeBoundingBox(pos: NodePosition, clearance = 0): BoundingBox {
  const halfW = NODE_WIDTH / 2;
  return {
    left: pos.x - halfW - clearance,
    right: pos.x + halfW + clearance,
    top: pos.y - clearance,
    bottom: pos.y + NODE_HEIGHT + clearance,
    centerX: pos.x,
    centerY: pos.y + NODE_HEIGHT / 2,
  };
}

/**
 * Checks if two nodes overlap taking the minimum clearance into account.
 */
export function doNodesCollide(
  posA: NodePosition,
  posB: NodePosition,
  clearance = DEFAULT_CLEARANCE
): boolean {
  const boxA = getNodeBoundingBox(posA);
  const boxB = getNodeBoundingBox(posB);

  // Axis-Aligned Bounding Box overlap test with clearance buffer
  const overlapX = boxA.right + clearance > boxB.left && boxA.left - clearance < boxB.right;
  const overlapY = boxA.bottom + clearance > boxB.top && boxA.top - clearance < boxB.bottom;

  return overlapX && overlapY;
}

/**
 * Returns list of node IDs that are within magnetic proximity (< threshold) of the active node.
 */
export function getProximityNodeIds(
  activeNodeId: string,
  activePos: NodePosition,
  nodes: GraphNodeDto[],
  getNodePos: (n: GraphNodeDto) => NodePosition,
  threshold = 25
): string[] {
  const proximityIds: string[] = [];

  for (const other of nodes) {
    if (other.id === activeNodeId) continue;
    const otherPos = getNodePos(other);
    if (doNodesCollide(activePos, otherPos, threshold)) {
      proximityIds.push(other.id);
    }
  }

  return proximityIds;
}

/**
 * Resolves magnetic collision and repulsion for a moved node against all other static nodes.
 * Pushes the active node to the nearest stable, non-overlapping location maintaining >= clearance.
 */
export function resolveMagneticRepulsion(
  activeNodeId: string,
  proposedPos: NodePosition,
  nodes: GraphNodeDto[],
  getNodePos: (n: GraphNodeDto) => NodePosition,
  clearance = DEFAULT_CLEARANCE
): NodePosition {
  let resolvedX = proposedPos.x;
  let resolvedY = proposedPos.y;

  // Maximum relaxation iterations to prevent any infinite loops while settling multiple collisions
  const MAX_ITERATIONS = 6;

  for (let iter = 0; iter < MAX_ITERATIONS; iter++) {
    let hasCollision = false;

    for (const other of nodes) {
      if (other.id === activeNodeId) continue;
      const otherPos = getNodePos(other);

      const currentPos: NodePosition = { x: resolvedX, y: resolvedY };
      if (!doNodesCollide(currentPos, otherPos, clearance)) {
        continue;
      }

      hasCollision = true;

      const activeBox = getNodeBoundingBox(currentPos);
      const otherBox = getNodeBoundingBox(otherPos);

      // Calculate penetration depths along both axes
      const deltaX = activeBox.centerX - otherBox.centerX;
      const deltaY = activeBox.centerY - otherBox.centerY;

      const targetDistanceX = NODE_WIDTH + clearance;
      const targetDistanceY = NODE_HEIGHT + clearance;

      const overlapX = targetDistanceX - Math.abs(deltaX);
      const overlapY = targetDistanceY - Math.abs(deltaY);

      if (overlapX <= 0 || overlapY <= 0) continue;

      // Project along minimum displacement axis
      // If moving predominantly horizontally or vertically, separate along that axis
      const ratioX = overlapX / targetDistanceX;
      const ratioY = overlapY / targetDistanceY;

      if (ratioX < ratioY) {
        // Separate horizontally
        const pushDirection = deltaX >= 0 ? 1 : -1;
        resolvedX = otherBox.centerX + pushDirection * targetDistanceX;
      } else {
        // Separate vertically
        const pushDirection = deltaY >= 0 ? 1 : -1;
        resolvedY = otherBox.centerY + pushDirection * targetDistanceY - NODE_HEIGHT / 2;
      }
    }

    if (!hasCollision) {
      break;
    }
  }

  return {
    x: Math.round(resolvedX),
    y: Math.round(resolvedY),
  };
}
