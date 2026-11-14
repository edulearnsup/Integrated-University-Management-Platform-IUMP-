import {
  GraphResponseDto,
  RoomDetailDto,
  SessionAccountOption,
  SkillEdgeDto,
  SkillLearningStatus,
  SkillNodeDto,
  SkillNodeType,
  SkillPortSide,
  SkillProficiencyLevel,
  SkillRelationType,
  StudentPublicDetailDto,
  StudentSkillGraphResponseDto,
  SystemDomainDto,
} from '../domain/types';

export class ApiRequestError extends Error {
  public readonly status: number;
  public readonly code: string;

  constructor(message: string, status: number, code = 'API_ERROR') {
    super(message);
    this.name = 'ApiRequestError';
    this.status = status;
    this.code = code;
  }
}

function isPhpWampMode(): boolean {
  if (typeof window === 'undefined') return false;
  const params = new URLSearchParams(window.location.search);
  return (
    params.get('mode') === 'php' ||
    window.location.pathname.includes('/skills_graph') ||
    window.location.pathname.includes('/dorm_graph')
  );
}

function getPhpSkillsApiUrl(action: string, extraParams?: Record<string, string>): string {
  const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
  const idx = pathname.indexOf('/skills_graph');
  const base =
    idx !== -1 ? `${pathname.slice(0, idx)}/get_skills_graph.php` : '../get_skills_graph.php';
  const search = new URLSearchParams({ action, ...(extraParams || {}) });
  return `${base}?${search.toString()}`;
}

function isRealJwt(token: string): boolean {
  return typeof token === 'string' && token.split('.').length === 3;
}

function buildAuthHeaders(sessionToken: string, simulateOutage = false): HeadersInit {
  const headers: Record<string, string> = {
    Accept: 'application/json',
  };
  if (!isPhpWampMode() || isRealJwt(sessionToken)) {
    headers.Authorization = `Bearer ${sessionToken}`;
  }
  if (simulateOutage) {
    headers['X-Simulate-Service-Unavailable'] = '1';
  }
  return headers;
}

async function parseApiJson<T>(response: Response): Promise<T> {
  let payload: unknown = null;
  try {
    payload = await response.json();
  } catch {
    throw new ApiRequestError(
      'ارتباط با سرور سامانه برقرار نشد. لطفاً دوباره تلاش کنید.',
      response.status || 500,
      'INVALID_JSON'
    );
  }

  if (!response.ok) {
    const errObj = payload as { message?: string; errorCode?: string } | null;
    throw new ApiRequestError(
      errObj?.message || 'پردازش درخواست با مشکل مواجه شد. لطفاً دوباره تلاش کنید.',
      response.status,
      errObj?.errorCode || 'HTTP_ERROR'
    );
  }

  return payload as T;
}

export async function fetchMyDormitoryGraph(
  sessionToken: string,
  simulateOutage = false
): Promise<GraphResponseDto> {
  const response = await fetch('/api/v1/graph/dormitory/me', {
    method: 'GET',
    headers: buildAuthHeaders(sessionToken, simulateOutage),
  });
  return parseApiJson<GraphResponseDto>(response);
}

export async function fetchAuthorizedRoomDetails(
  sessionToken: string,
  roomId: string
): Promise<RoomDetailDto> {
  const response = await fetch(`/api/v1/graph/dormitory/rooms/${encodeURIComponent(roomId)}`, {
    method: 'GET',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<RoomDetailDto>(response);
}

export async function fetchAuthorizedStudentDetails(
  sessionToken: string,
  studentId: string
): Promise<StudentPublicDetailDto> {
  const response = await fetch(
    `/api/v1/graph/dormitory/students/${encodeURIComponent(studentId)}`,
    {
      method: 'GET',
      headers: buildAuthHeaders(sessionToken),
    }
  );
  return parseApiJson<StudentPublicDetailDto>(response);
}

export async function fetchAvailableSessions(): Promise<SessionAccountOption[]> {
  const response = await fetch('/api/v1/auth/sessions', {
    method: 'GET',
    headers: { Accept: 'application/json' },
  });
  return parseApiJson<SessionAccountOption[]>(response);
}

export interface SecurityProbeResult {
  endpoint: string;
  method: string;
  httpStatus: number;
  ok: boolean;
  responseBody: unknown;
}

export async function runSecurityProbe(params: {
  sessionToken?: string;
  endpoint: string;
  method?: 'GET' | 'POST' | 'DELETE';
}): Promise<SecurityProbeResult> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };
  if (params.sessionToken) {
    headers.Authorization = `Bearer ${params.sessionToken}`;
  }

  const response = await fetch(params.endpoint, {
    method: params.method ?? 'GET',
    headers,
    body: params.method === 'POST' ? JSON.stringify({ label: 'تلاش تغییر غیرمجاز' }) : undefined,
  });

  let responseBody: unknown = null;
  try {
    responseBody = await response.json();
  } catch {
    responseBody = { message: 'پاسخ غیر JSON' };
  }

  return {
    endpoint: params.endpoint,
    method: params.method ?? 'GET',
    httpStatus: response.status,
    ok: response.ok,
    responseBody,
  };
}

/* =========================================================================
 * Version 2.0: Student Visual Skills & Talent Graph API Client
 * ========================================================================= */

export async function fetchMyStudentSkills(
  sessionToken: string
): Promise<StudentSkillGraphResponseDto> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('me'), {
      method: 'GET',
      credentials: 'include',
      headers: buildAuthHeaders(sessionToken),
    });
    return parseApiJson<StudentSkillGraphResponseDto>(response);
  }
  const response = await fetch('/api/v1/graph/skills/me', {
    method: 'GET',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<StudentSkillGraphResponseDto>(response);
}

export async function createSkillNodeApi(
  sessionToken: string,
  payload: {
    title: string;
    nodeType: SkillNodeType;
    subtitle?: string;
    domainId?: string;
    category?: string;
    proficiencyLevel?: SkillProficiencyLevel;
    numericLevel?: number;
    learningStatus?: SkillLearningStatus;
    position?: { x: number; y: number };
    notes?: string;
    autoConnect?: boolean;
  }
): Promise<SkillNodeDto> {
  const url = isPhpWampMode() ? getPhpSkillsApiUrl('create_node') : '/api/v1/graph/skills/nodes';
  const response = await fetch(url, {
    method: 'POST',
    credentials: isPhpWampMode() ? 'include' : 'same-origin',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return parseApiJson<SkillNodeDto>(response);
}

export async function updateSkillNodeApi(
  sessionToken: string,
  nodeId: string,
  payload: Partial<SkillNodeDto>
): Promise<SkillNodeDto> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('update_node', { node_id: nodeId }), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ...payload, nodeId }),
    });
    return parseApiJson<SkillNodeDto>(response);
  }
  const response = await fetch(`/api/v1/graph/skills/nodes/${encodeURIComponent(nodeId)}`, {
    method: 'PUT',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return parseApiJson<SkillNodeDto>(response);
}

export async function deleteSkillNodeApi(
  sessionToken: string,
  nodeId: string
): Promise<{ success: boolean }> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('delete_node', { node_id: nodeId }), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ nodeId }),
    });
    return parseApiJson<{ success: boolean }>(response);
  }
  const response = await fetch(`/api/v1/graph/skills/nodes/${encodeURIComponent(nodeId)}`, {
    method: 'DELETE',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<{ success: boolean }>(response);
}

export async function createSkillEdgeApi(
  sessionToken: string,
  payload: {
    source: string;
    target: string;
    relationType?: SkillRelationType;
    label?: string;
    sourceHandle?: SkillPortSide;
    targetHandle?: SkillPortSide;
  }
): Promise<SkillEdgeDto> {
  const url = isPhpWampMode() ? getPhpSkillsApiUrl('create_edge') : '/api/v1/graph/skills/edges';
  const response = await fetch(url, {
    method: 'POST',
    credentials: isPhpWampMode() ? 'include' : 'same-origin',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return parseApiJson<SkillEdgeDto>(response);
}

export async function updateSkillEdgeApi(
  sessionToken: string,
  edgeId: string,
  payload: {
    label?: string;
    relationType?: SkillRelationType;
    sourceHandle?: SkillPortSide;
    targetHandle?: SkillPortSide;
  }
): Promise<SkillEdgeDto> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('update_edge', { edge_id: edgeId }), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ ...payload, edgeId }),
    });
    return parseApiJson<SkillEdgeDto>(response);
  }
  const response = await fetch(`/api/v1/graph/skills/edges/${encodeURIComponent(edgeId)}`, {
    method: 'PUT',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(payload),
  });
  return parseApiJson<SkillEdgeDto>(response);
}

export async function deleteSkillEdgeApi(
  sessionToken: string,
  edgeId: string
): Promise<{ success: boolean }> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('delete_edge', { edge_id: edgeId }), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ edgeId }),
    });
    return parseApiJson<{ success: boolean }>(response);
  }
  const response = await fetch(`/api/v1/graph/skills/edges/${encodeURIComponent(edgeId)}`, {
    method: 'DELETE',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<{ success: boolean }>(response);
}

export async function resetStudentSkillsApi(
  sessionToken: string
): Promise<StudentSkillGraphResponseDto> {
  const url = isPhpWampMode() ? getPhpSkillsApiUrl('reset') : '/api/v1/graph/skills/reset';
  const response = await fetch(url, {
    method: 'POST',
    credentials: isPhpWampMode() ? 'include' : 'same-origin',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<StudentSkillGraphResponseDto>(response);
}

export async function fetchSystemDomainsApi(): Promise<SystemDomainDto[]> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('domains'), {
      method: 'GET',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    });
    return parseApiJson<SystemDomainDto[]>(response);
  }
  const response = await fetch('/api/skills/domains', {
    method: 'GET',
    headers: { Accept: 'application/json' },
  });
  return parseApiJson<SystemDomainDto[]>(response);
}

export async function attachDomainApi(
  sessionToken: string,
  domainId: string,
  relationKind = 'interest'
): Promise<SkillNodeDto> {
  const url = isPhpWampMode()
    ? getPhpSkillsApiUrl('attach_domain')
    : '/api/graph/skills/me/domains';
  const response = await fetch(url, {
    method: 'POST',
    credentials: isPhpWampMode() ? 'include' : 'same-origin',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ domainId, relationKind }),
  });
  return parseApiJson<SkillNodeDto>(response);
}

export async function detachDomainApi(
  sessionToken: string,
  domainId: string
): Promise<{ success: boolean }> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('detach_domain', { domain_id: domainId }), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ domainId }),
    });
    return parseApiJson<{ success: boolean }>(response);
  }
  const response = await fetch(`/api/graph/skills/me/domains/${encodeURIComponent(domainId)}`, {
    method: 'DELETE',
    headers: buildAuthHeaders(sessionToken),
  });
  return parseApiJson<{ success: boolean }>(response);
}

export async function saveGraphLayoutApi(
  sessionToken: string,
  positions: Array<{ nodeId: string; x: number; y: number }>
): Promise<{ success: boolean; layoutVersion: number }> {
  if (isPhpWampMode()) {
    const response = await fetch(getPhpSkillsApiUrl('save_layout'), {
      method: 'POST',
      credentials: 'include',
      headers: {
        ...buildAuthHeaders(sessionToken),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ positions }),
    });
    return parseApiJson<{ success: boolean; layoutVersion: number }>(response);
  }
  const response = await fetch('/api/graph/skills/me/layout', {
    method: 'PUT',
    headers: {
      ...buildAuthHeaders(sessionToken),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ positions }),
  });
  return parseApiJson<{ success: boolean; layoutVersion: number }>(response);
}



