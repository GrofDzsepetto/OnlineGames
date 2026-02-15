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

  if (!res.ok)
    throw new Error(data?.error || `HTTP ${res.status}`);

  return (data ?? []).map((q: any) => ({
    id: String(q.id),
    slug: q.slug,
    title: q.title,
    description: q.description ?? null,
    creator_name: q.creator_name ?? undefined,
    created_by: String(q.created_by),
    language: (q.language_code ?? "hu").toLowerCase(),
    is_public: Boolean(q.is_public),
  }));
}


/* -------------------------------------------------------
   GET QUESTIONS (PLAY)
------------------------------------------------------- */

export async function getQuizQuestions(slugOrId: string): Promise<QuizQuestion[]> {
  console.log("======== FETCH START ========");
  console.log("SLUG OR ID:", slugOrId);

  const res = await fetch(
    `${API_BASE}/quiz.php?slug=${encodeURIComponent(slugOrId)}`,
    { credentials: "include" }
  );

  console.log("HTTP STATUS:", res.status);

  const raw = await res.text();
  console.log("RAW RESPONSE TEXT:", raw);

  let json: ApiQuizResponse;

  try {
    json = JSON.parse(raw);
  } catch {
    console.error("JSON PARSE ERROR");
    throw new Error("Invalid JSON from quiz.php:\n" + raw.slice(0, 300));
  }

  console.log("PARSED JSON:", json);

  if (!res.ok) {
    console.error("HTTP NOT OK:", json);
    throw new Error((json as any)?.error ?? `HTTP ${res.status}`);
  }

  console.log("QUIZ OBJECT:", json.quiz);
  console.log("QUESTIONS FIELD:", json?.quiz?.questions);

  const apiQuestions = json?.quiz?.questions ?? [];

  console.log("QUESTIONS LENGTH:", apiQuestions.length);
  console.log("======== FETCH END ========");

  return apiQuestions.map((q: ApiQuestion): QuizQuestion => {
    console.log("MAPPING QUESTION:", q);

    if (q.type === "MATCHING") {
      return {
        id: q.id,
        type: "MATCHING",
        question: q.question_text,
        pairs: (q.groups ?? []).map((g) => ({
          left: (g.left ?? "").toString(),
          rights: (g.right ?? []).map((x) => x.toString()),
        })),
      };
    }

    return {
      id: q.id,
      type: "MULTIPLE_CHOICE",
      question: q.question_text,
      answers: (q.answers ?? []).map((a) => ({
        text: a.answer_text,
        correct: toBool(a.is_correct),
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

  const quiz = json.quiz;

  const questions = (quiz?.questions ?? []).map(
    (q: ApiQuestion): QuizQuestion => {
      if (q.type === "MATCHING") {
        return {
          id: q.id,
          type: "MATCHING",
          question: q.question_text,
          pairs: (q.groups ?? []).map((g) => ({
            left: (g.left?.[0] ?? "").toString(),
            rights: (g.right ?? []).map((x) => x.toString()),
          })),
        };
      }

      return {
        id: q.id,
        type: "MULTIPLE_CHOICE",
        question: q.question_text,
        answers: (q.answers ?? []).map((a) => ({
          text: a.answer_text,
          correct: toBool(a.is_correct),
        })),
      };
    }
  );

  return {
    quiz_id: String(quiz.id),
    title: String(quiz.title ?? ""),
    description: String(quiz.description ?? ""),
    language: String(quiz.language_code ?? "hu"),
    isPublic: toBool(quiz.is_public),
    viewerEmails: (quiz.viewers_email ?? []).map((e: any) =>
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
    body: JSON.stringify(payload),
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

  const raw = await res.text();

  let json: any;
  try {
    json = JSON.parse(raw);
  } catch {
    throw new Error("Invalid JSON from quiz.php:\n" + raw.slice(0, 300));
  }

  if (!res.ok) {
    throw new Error(json?.error ?? "Failed to load quiz");
  }

  const q = json.quiz ?? json.QUIZ ?? json;

  if (!q) {
    throw new Error("Quiz data missing from response");
  }

  return {
    id: q.id ?? q.ID,
    title: q.title ?? q.TITLE,
    description: q.description ?? q.DESCRIPTION,
    creator_name: q.creator_name ?? q.CREATOR_NAME,
    language: (q.language_code ?? q.LANGUAGE_CODE ?? "hu"),
    isPublic: (q.is_public ?? q.IS_PUBLIC) === 1,
  };
}



export async function getQuizResults(slug: string){
  const res = await fetch(
    `${API_BASE}/get_results.php?slug=${encodeURIComponent(slug)}`,
    { credentials: "include" }
  );

  const json = await res.json().catch(() => null);

  if (!res.ok) {
    throw new Error(json?.error ?? `HTTP ${res.status}`);
  }

  return {
    slug,
    results: Array.isArray(json.results) ? json.results : [],
  };
}


export async function saveQuizResult(payload: {
  quiz_slug: string;
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
  throw new Error(
    json?.details || json?.error || `HTTP ${res.status}`
  );
}


  return json;
}