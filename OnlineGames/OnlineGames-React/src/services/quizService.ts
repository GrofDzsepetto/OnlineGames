import { API_BASE } from "../config/api";
import type { Quiz, QuizQuestion, MatchingPair } from "../types/quiz";
import type { ApiQuizResponse, ApiQuestion } from "./quizApi.types";

const toBool = (v: any) => v === 1 || v === "1" || v === true;

/* -------------------------------------------------------
   TYPES
------------------------------------------------------- */

export type CreateQuizPayload = {
  title: string;
  description: string;
  language: string; 
  questions: Array<{
    text: string;
    type: "MULTIPLE_CHOICE" | "MATCHING";
    answers: Array<{ text: string; isCorrect: boolean }>;
    pairs: MatchingPair[];
  }>;
  isPublic?: boolean;
  viewerEmails?: string[];
};

export type QuizForEdit = {
  quiz_id: string;
  title: string;
  description: string;
  language: string;
  isPublic: boolean;
  viewerEmails: string[];
  questions: QuizQuestion[];
};



/* -------------------------------------------------------
   GET LIST
------------------------------------------------------- */

export async function getQuizzes(): Promise<Quiz[]> {
  const res = await fetch(`${API_BASE}/quizzes.php`, {
    credentials: "include",
  });

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

/* -------------------------------------------------------
   GET QUESTIONS (PLAY)
------------------------------------------------------- */

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

/* -------------------------------------------------------
   GET FOR EDIT
------------------------------------------------------- */

export async function getQuizForEdit(
  slugOrId: string
): Promise<QuizForEdit> {
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

  const quiz = json.QUIZ;

  const questions = (quiz?.QUESTIONS ?? []).map(
    (q: ApiQuestion): QuizQuestion => {
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
    }
  );

  return {
    quiz_id: String(quiz.ID),
    title: String(quiz.TITLE ?? ""),
    description: String(quiz.DESCRIPTION ?? ""),
    language: String(quiz.LANGUAGE_CODE ?? "hu"),
    isPublic: toBool(quiz.IS_PUBLIC),
    viewerEmails: (quiz.VIEWER_EMAILS ?? []).map((e: any) =>
      String(e.EMAIL ?? e)
    ),
    questions,
  };
}



/* -------------------------------------------------------
   CREATE
------------------------------------------------------- */

export async function createQuiz(
  payload: CreateQuizPayload
): Promise<{ quiz_id: string; slug: string }> {
  const res = await fetch(`${API_BASE}/create_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload), // ✅ language is benne
  });

  const raw = await res.text();

  let data: any;
  try {
    data = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from create_quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok)
    throw new Error(data?.error || `Create failed (HTTP ${res.status})`);

  return {
    quiz_id: String(data.quiz_id),
    slug: String(data.slug),
  };
}

/* -------------------------------------------------------
   UPDATE
------------------------------------------------------- */

export async function updateQuiz(
  payload: { quiz_id: string } & CreateQuizPayload
): Promise<void> {
  const res = await fetch(`${API_BASE}/update_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const raw = await res.text();

  let data: any;
  try {
    data = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from update_quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok)
    throw new Error(data?.error || `Update failed (HTTP ${res.status})`);
}


/* -------------------------------------------------------
   DELETE
------------------------------------------------------- */

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

  if (!res.ok)
    throw new Error(data?.error || `Delete failed (HTTP ${res.status})`);
}


export async function getQuizMeta(slug: string) {
  const res = await fetch(
    `${API_BASE}/quiz.php?slug=${encodeURIComponent(slug)}`,
    { credentials: "include" }
  );

  const json = await res.json();
  if (!res.ok) throw new Error(json?.error ?? "Failed to load quiz");

  const q = json.QUIZ;

  return {
    id: q.ID,
    title: q.TITLE,
    description: q.DESCRIPTION,
    creator_name: q.CREATOR_NAME,
    language: q.LANGUAGE_CODE ?? "hu",
    isPublic: q.IS_PUBLIC === 1,
  };
}


export async function getQuizResults(quizId: string) {
  const res = await fetch(`${API_BASE}/get_results.php?quiz_id=${encodeURIComponent(quizId)}`, {
    credentials: "include",
  });

  const json = await res.json().catch(() => null);

  if (!res.ok) {
    throw new Error(json?.error ?? `HTTP ${res.status}`);
  }

  return {
    quiz_id: String(json.quiz_id ?? quizId),
    results: Array.isArray(json.results) ? json.results : [],
  };
}


export async function saveQuizResult(payload: {
  quiz_id: string;
  score: number;
  max_score: number;
}) {
  const res = await fetch(`${API_BASE}/upsert_quiz_attempt.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const raw = await res.text();

  let json: any;
  try {
    json = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from upsert_quiz_attempt.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) {
    throw new Error(json?.error ?? `HTTP ${res.status}`);
  }

  return json;
}