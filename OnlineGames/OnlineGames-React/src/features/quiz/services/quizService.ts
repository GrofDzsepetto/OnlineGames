import { API_BASE } from "../../../config/api";
import type { Quiz, QuizQuestion, MatchingPair } from "../types/quiz";
import type { ApiQuizResponse, ApiQuestion } from "./quizApi.types";

const toBool = (v: any) => v === 1 || v === "1" || v === true;

const getErrorMessage = (data: any, fallback: string) =>
  data?.message || data?.error || fallback;

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

export async function getQuizzes(): Promise<Quiz[]> {
  const res = await fetch(`${API_BASE}/quizzes.php`, {
    credentials: "include",
  });

  const data = await res.json();

  if (!res.ok || !data?.success) {
    throw new Error(getErrorMessage(data, `HTTP ${res.status}`));
  }

  const quizzes = data?.data?.quizzes ?? [];

  return quizzes.map((q: any) => ({
    id: String(q.id),
    slug: q.slug,
    title: q.title,
    description: q.description ?? null,
    creator_name: q.creator_name ?? undefined,
    created_by: String(q.created_by),
    language: (q.language_code ?? "hu").toLowerCase(),
    is_public: toBool(q.is_public),
  }));
}

export async function getQuizQuestions(slugOrId: string): Promise<QuizQuestion[]> {
  const res = await fetch(
    `${API_BASE}/quiz.php?slug=${encodeURIComponent(slugOrId)}`,
    { credentials: "include" }
  );

  const json: ApiQuizResponse = await res.json();

  if (!res.ok || !json?.success) {
    throw new Error(getErrorMessage(json, `HTTP ${res.status}`));
  }

  const apiQuestions = json.data?.quiz?.questions ?? [];

  return apiQuestions.map((q: ApiQuestion): QuizQuestion => {
    const questionText = q.question_text ?? q.question ?? "";

    if (q.type === "MATCHING") {
      return {
        id: q.id ?? "",
        type: "MATCHING",
        question: questionText,
        pairs: (q.groups ?? []).map((g) => ({
          left: String(g.left ?? ""),
          rights: (g.right ?? []).map((x) => String(x)),
        })),
      };
    }

    return {
      id: q.id ?? "",
      type: "MULTIPLE_CHOICE",
      question: questionText,
      answers: (q.answers ?? []).map((a) => ({
        text: String(a.answer_text ?? a.label ?? a.text ?? ""),
        correct: toBool(a.is_correct ?? a.isCorrect),
      })),
    };
  });
}

export async function getQuizForEdit(slugOrId: string): Promise<QuizForEdit> {
  const res = await fetch(
    `${API_BASE}/get_quiz_for_edit.php?id=${encodeURIComponent(slugOrId)}`,
    { credentials: "include" }
  );

  const json = await res.json();

  if (!res.ok || !json?.success) {
    throw new Error(getErrorMessage(json, `HTTP ${res.status}`));
  }

  const quiz = json.data?.quiz;

  if (!quiz) {
    throw new Error("Quiz data missing from response");
  }

  const questions: QuizQuestion[] = (quiz.questions ?? []).map((q: any) => {
    if (q.type === "MATCHING") {
      return {
        id: q.id ?? "",
        type: "MATCHING",
        question: q.question ?? q.question_text ?? "",
        pairs: q.pairs ?? [],
      };
    }

    return {
      id: q.id ?? "",
      type: "MULTIPLE_CHOICE",
      question: q.question ?? q.question_text ?? "",
      answers: (q.answers ?? []).map((a: any) => ({
        text: String(a.text ?? a.answer_text ?? a.label ?? ""),
        correct: toBool(a.correct ?? a.isCorrect ?? a.is_correct),
      })),
    };
  });

  return {
    quiz_id: String(quiz.quiz_id ?? quiz.id),
    title: String(quiz.title ?? ""),
    description: String(quiz.description ?? ""),
    language: String(quiz.language ?? quiz.language_code ?? "hu"),
    isPublic: toBool(quiz.isPublic ?? quiz.is_public),
    viewerEmails: quiz.viewerEmails ?? quiz.viewer_emails ?? [],
    questions,
  };
}

export async function createQuiz(
  payload: CreateQuizPayload
): Promise<{ quiz_id: string; slug: string }> {
  const res = await fetch(`${API_BASE}/create_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const data = await res.json();

  if (!res.ok || !data?.success) {
    throw new Error(getErrorMessage(data, `Create failed (HTTP ${res.status})`));
  }

  return {
    quiz_id: String(data.data.quiz_id),
    slug: String(data.data.slug),
  };
}

export async function updateQuiz(
  payload: { quiz_id: string } & CreateQuizPayload
): Promise<void> {
  const res = await fetch(`${API_BASE}/update_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify(payload),
  });

  const data = await res.json();

  if (!res.ok || !data?.success) {
    throw new Error(getErrorMessage(data, `Update failed (HTTP ${res.status})`));
  }
}

export async function deleteQuiz(quizId: string): Promise<void> {
  const res = await fetch(`${API_BASE}/delete_quiz.php`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    credentials: "include",
    body: JSON.stringify({ quiz_id: quizId }),
  });

  const data = await res.json().catch(() => null);

  if (!res.ok || !data?.success) {
    throw new Error(getErrorMessage(data, `Delete failed (HTTP ${res.status})`));
  }
}

export async function getQuizMeta(slug: string) {
  const res = await fetch(
    `${API_BASE}/quiz.php?slug=${encodeURIComponent(slug)}`,
    { credentials: "include" }
  );

  const json = await res.json();

  if (!res.ok || !json?.success) {
    throw new Error(getErrorMessage(json, "Failed to load quiz"));
  }

  const q = json.data?.quiz;

  if (!q) {
    throw new Error("Quiz data missing from response");
  }

  return {
    id: q.id,
    title: q.title,
    description: q.description,
    creator_name: q.creator_name,
    language: q.language_code ?? "hu",
    isPublic: toBool(q.is_public),
  };
}

export async function getQuizResults(slug: string) {
  const res = await fetch(
    `${API_BASE}/get_results.php?slug=${encodeURIComponent(slug)}`,
    { credentials: "include" }
  );

  const json = await res.json().catch(() => null);

  if (!res.ok || !json?.success) {
    throw new Error(getErrorMessage(json, `HTTP ${res.status}`));
  }

  return {
    slug: json.data?.slug ?? slug,
    results: Array.isArray(json.data?.results) ? json.data.results : [],
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

  const json = await res.json();

  if (!res.ok || !json?.success) {
    throw new Error(getErrorMessage(json, `HTTP ${res.status}`));
  }

  return json.data;
}