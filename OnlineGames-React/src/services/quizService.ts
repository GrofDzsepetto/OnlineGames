import { API_BASE } from "../config/api";
import type { Quiz, QuizQuestion, MatchingPair } from "../types/quiz";
import type { ApiQuizResponse, ApiQuestion } from "./quizApi.types";

const toBool = (v: any) => v === 1 || v === "1" || v === true;

export async function getQuizzes(): Promise<Quiz[]> {
  const res = await fetch(`${API_BASE}/quizzes.php`, { credentials: "include" });
  const raw = await res.text();
  const data = JSON.parse(raw);

  if (!res.ok) throw new Error(data?.error || `HTTP ${res.status}`);

  return (data ?? []).map((q: any) => ({
    id: String(q.ID ?? q.id),
    slug: q.SLUG ?? q.slug,
    title: q.TITLE ?? q.title,
    description: q.DESCRIPTION ?? q.description ?? null,
    creator_name: q.CREATOR_NAME ?? q.creator_name,
    created_by: q.CREATED_BY != null ? String(q.CREATED_BY) : undefined,
  }));
}

export async function getQuizQuestions(slugOrId: string): Promise<QuizQuestion[]> {
  const res = await fetch(
    `${API_BASE}/quiz.php?slug=${encodeURIComponent(slugOrId)}`,
    { credentials: "include" }
  );

  const raw = await res.text();

  let json: ApiQuizResponse;
  try {
    json = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) throw new Error((json as any)?.error ?? `HTTP ${res.status}`);

  const apiQuestions = json?.QUIZ?.QUESTIONS ?? [];

  return apiQuestions.map((q: ApiQuestion): QuizQuestion => {
    if (q.TYPE === "MATCHING") {
      return {
        id: q.ID,
        type: "MATCHING",
        question: q.QUESTION_TEXT,
        pairs: (q.GROUPS ?? []).map((g) => ({
          left: (g.LEFT?.[0] ?? "").toString(),
          rights: (g.RIGHT ?? []).map((x) => x.toString()),
        })),
      };
    }

    return {
      id: q.ID,
      type: "MULTIPLE_CHOICE",
      question: q.QUESTION_TEXT,
      answers: (q.ANSWERS ?? []).map((a) => ({
        text: a.ANSWER_TEXT,
        correct: toBool(a.IS_CORRECT),
      })),
    };
  });
}

export async function deleteQuiz(quizId: string): Promise<void> {
  const res = await fetch(`${API_BASE}/delete_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ quiz_id: quizId }),
  });

  const raw = await res.text();
  let data: any = null;
  try {
    data = JSON.parse(raw);
  } catch {
    data = null;
  }

  if (!res.ok) throw new Error(data?.error || `Delete failed (HTTP ${res.status})`);
}

export type CreateQuizPayload = {
  title: string;
  description: string;
  questions: Array<{
    text: string;
    type: "MULTIPLE_CHOICE" | "MATCHING";
    answers: Array<{ text: string; isCorrect: boolean }>;
    pairs: MatchingPair[];
  }>;
};

export async function createQuiz(payload: CreateQuizPayload): Promise<{ quiz_id: string; slug: string }> {
  const res = await fetch(`${API_BASE}/create_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const raw = await res.text();

  let data: any = null;
  try {
    data = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from create_quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) throw new Error(data?.error || `Create failed (HTTP ${res.status})`);

  return { quiz_id: String(data.quiz_id), slug: String(data.slug) };
}

export type QuizForEdit = {
  quiz_id: string;
  title: string;
  description: string;
  questions: QuizQuestion[];
};

export async function getQuizForEdit(idOrSlug: string): Promise<QuizForEdit & { isPublic: boolean; viewerEmails: string[] }> {
  const res = await fetch(
    `${API_BASE}/get_quiz_for_edit.php?id=${encodeURIComponent(idOrSlug)}`,
    { credentials: "include" }
  );

  const raw = await res.text();

  let json: any;
  try {
    json = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from get_quiz_for_edit.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) {
    throw new Error(json?.error ?? `HTTP ${res.status}`);
  }

  return {
    quiz_id: String(json.quiz_id ?? ""),
    title: String(json.title ?? ""),
    description: String(json.description ?? ""),
    isPublic: !!json.isPublic,
    viewerEmails: Array.isArray(json.viewerEmails) ? json.viewerEmails.map((x: any) => String(x)) : [],
    questions: Array.isArray(json.questions)
      ? json.questions.map((q: any) => ({
          type: q.type,
          question: q.question,
          answers:
            q.type === "MULTIPLE_CHOICE"
              ? (Array.isArray(q.answers) ? q.answers : []).map((a: any) => ({
                  text: String(a.text ?? ""),
                  correct: !!a.isCorrect,
                }))
              : [],
          pairs:
            q.type === "MATCHING"
              ? (Array.isArray(q.pairs) ? q.pairs : []).map((p: any) => ({
                  left: String(p.left ?? ""),
                  rights: Array.isArray(p.rights) ? p.rights.map((r: any) => String(r)) : [],
                }))
              : [],
        }))
      : [],
  };
}


export async function updateQuiz(payload: { quiz_id: string } & CreateQuizPayload): Promise<void> {
  const res = await fetch(`${API_BASE}/update_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const raw = await res.text();

  let data: any = null;
  try {
    data = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from update_quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) throw new Error(data?.error || `Update failed (HTTP ${res.status})`);
}
