import { createBrowserRouter } from "react-router-dom";
import Layout from "../components/layout/Layout";
import Home from "../pages/Home";
import QuizList from "../pages/Quiz/QuizList";
import QuizPlay from "../pages/Quiz/QuizPlay";
import NotFound from "../pages/NotFound";
import Login from "../pages/Login";
import CreateQuiz from "../pages/Quiz/CreateQuiz";
import EditQuiz from "../pages/Quiz/EditQuiz";
import Help from "../pages/Help";
import QuizInfo from "../components/quiz/QuizInfo";
import HostQuiz from "../pages/Live-Quiz/HostQuiz";
import JoinGame from "../pages/Live-Quiz/JoinGame";
import PlayerGame from "../pages/Live-Quiz/PlayerGame";

export const router = createBrowserRouter([
  {
    element: <Layout />,
    children: [
      { path: "/", element: <Home /> },
      { path: "/quizzes", element: <QuizList /> },
      { path: "/quiz/:slug", element: <QuizInfo /> },
      { path: "/play/:slug", element: <QuizPlay /> },

      { path: "/login", element: <Login /> },
      { path: "/create-quiz", element: <CreateQuiz /> },
      { path: "/edit-quiz/:id", element: <EditQuiz /> },
      { path: "/help", element: <Help /> },
      {path: "/host/:slug", element: <HostQuiz />},
      {path: "/join", element: <JoinGame />},
      { path: "/play/:pin/:playerId", element: <PlayerGame /> }
    ],
  },
  { path: "*", element: <NotFound /> },
]);

 