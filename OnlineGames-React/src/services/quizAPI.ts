import type { Quiz, QuizQuestion } from "../types/quiz";

export const getQuizzes = async (): Promise<Quiz[]> => {
  const res = await fetch("/api/quizzes.php");

  if (!res.ok) {
    throw new Error("Failed to load quizzes");
  }

  const data = await res.json();

  return data.map((q: any) => ({
    id: q.SLUG,
    title: q.TITLE,
    description: q.DESCRIPTION,
  }));
};

export const getQuizQuestions = async (
  quizId: string
): Promise<QuizQuestion[]> => {
  const res = await fetch(`/api/quiz.php?slug=${quizId}`);

  if (!res.ok) {
    throw new Error("Failed to load quiz");
  }

  const data = await res.json();

  return data.QUIZ.QUESTIONS.map((q: any) => ({
    id: q.ID,
    question: q.QUESTION_TEXT,
    answers: q.ANSWERS.map((a: any) => a.ANSWER_TEXT),
  }));
};
