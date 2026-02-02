import type { Quiz, QuizQuestion } from "../types/quiz";
import { API_BASE } from "../config/api";

export const getQuizzes = async (): Promise<Quiz[]> => {
  const res = await fetch(`${API_BASE}/quizzes.php`);

  if (!res.ok) {
    throw new Error("Failed to load quizzes");
  }

  const data = await res.json();

  return data.map((q: any) => ({
    id: String(q.ID),
    slug: q.SLUG,
    title: q.TITLE,
    description: q.DESCRIPTION,
  }));
};

export const getQuizQuestions = async (slug: string): Promise<QuizQuestion[]> => {
  const url = `${API_BASE}/quiz.php?slug=${encodeURIComponent(slug)}`;
  const res = await fetch(url);

  // #Log if needed
  // const text = await res.text();
  // console.log("getQuizQuestions url:", url);
  // console.log("getQuizQuestions status:", res.status);
  // console.log("getQuizQuestions raw response:", text);

  if (!res.ok) {
    throw new Error(`Failed to load quiz (${res.status})`);
  }

  const data = await res.json();

  return (data?.QUIZ?.QUESTIONS ?? []).map((q: any) => {
    const base = {
      id: String(q.ID),
      type: q.TYPE as "SINGLE" | "MULTI" | "MATCHING",
      question: q.QUESTION_TEXT as string,
    };

    if (q.TYPE === "MATCHING") {
  return {
        ...base,
        groups: (q.GROUPS ?? []).map((g: any) => ({
          id: String(g.ID),
          left: (g.LEFT ?? []).map((x: any) => String(x)),
          right: (g.RIGHT ?? []).map((x: any) => String(x)),
        })),
      };
    }


    return {
      ...base,
      answers: (q.ANSWERS ?? []).map((a: any) => ({
        text: a.ANSWER_TEXT as string,
        correct: Number(a.IS_CORRECT) === 1,
      })),
    };
  });
};

