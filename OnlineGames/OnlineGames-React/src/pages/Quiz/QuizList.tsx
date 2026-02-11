import { useEffect, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { getQuizzes, deleteQuiz } from "../../services/quizService";
import type { Quiz } from "../../types/quiz";
import QuizCard from "../../components/quiz/QuizCard";
import { useAuth } from "../../auth/AuthContext";
import "../../styles/Quiz/QuizList.css";

const QuizList = () => {
  const navigate = useNavigate();

  const [quizzes, setQuizzes] = useState<Quiz[]>([]);
  const [deletingId, setDeletingId] = useState<string | null>(null);
  const { user } = useAuth();

  const load = async () => {
    try {
      const data = await getQuizzes();
      setQuizzes(data);
    } catch (err) {
      console.error("getQuizzes error:", err);
      setQuizzes([]);
    }
  };

  useEffect(() => {
    load();
  }, []);

  const onDelete = async (quiz: Quiz) => {
    if (!quiz.id) return;

    const ok = confirm(`Biztos törlöd a kvízt?\n\n${quiz.title}`);
    if (!ok) return;

    try {
      setDeletingId(quiz.id);
      await deleteQuiz(quiz.id);
      setQuizzes((prev) => prev.filter((q) => q.id !== quiz.id));
    } catch (e: any) {
      alert(e?.message ?? "Törlés sikertelen");
    } finally {
      setDeletingId(null);
    }
  };

  const userId = user?.id != null ? String(user.id) : null;

  return (
    <div>
      <div className="quizlist-header">
        <h2 className="quizlist-title">Select a Quiz</h2>

        {user && (
          <Link to="/create-quiz" className="create-quiz-btn">
            + Create Quiz
          </Link>
        )}
      </div>

      {quizzes.length > 0 ? (
        quizzes.map((quiz) => {
          const createdBy =
            (quiz as any).created_by ??
            (quiz as any).CREATED_BY ??
            null;

          const isOwner =
            userId != null &&
            createdBy != null &&
            String(createdBy) === userId;

          return (
            <div key={quiz.id} className="quiz-wrapper">
              {isOwner && (
                <div className="quiz-actions">
                  <button
                    type="button"
                    className="edit-btn"
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      navigate(`/edit-quiz/${quiz.id}`);
                    }}
                  >
                    Edit
                  </button>

                  <button
                    type="button"
                    className="delete-btn"
                    disabled={deletingId === quiz.id}
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      onDelete(quiz);
                    }}
                  >
                    {deletingId === quiz.id ? "Deleting..." : "Delete"}
                  </button>
                </div>
              )}

              <div
                onClick={() => navigate(`/quiz/${quiz.slug}`)}
                style={{ cursor: "pointer" }}
              >
                <QuizCard quiz={quiz} />
              </div>


              {quiz.creator_name && (
                <span className="creator-name">
                  Created by: {quiz.creator_name}
                </span>
              )}
            </div>
          );
        })
      ) : (
        <p>No quizzes available.</p>
      )}
    </div>
  );
};

export default QuizList;
