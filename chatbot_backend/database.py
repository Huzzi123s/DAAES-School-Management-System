
import pymysql
from config import Config


def get_school_knowledge():

    connection = None

    try:
        connection = pymysql.connect(
            host=Config.DB_HOST,
            port=Config.DB_PORT,
            user=Config.DB_USER,
            password=Config.DB_PASSWORD,
            database=Config.DB_NAME,
            charset="utf8mb4",
            cursorclass=pymysql.cursors.DictCursor,
            connect_timeout=5
        )

        knowledge = []

        with connection.cursor() as cursor:

            # Active FAQs
            cursor.execute("""
                SELECT category, question, answer, language, keywords
                FROM chatbot_faqs
                WHERE status = 'active'
            """)

            faqs = cursor.fetchall()

            for faq in faqs:
                knowledge.append(
                    f"FAQ Category: {faq['category']}\n"
                    f"Question: {faq['question']}\n"
                    f"Answer: {faq['answer']}\n"
                    f"Language: {faq['language']}\n"
                    f"Keywords: {faq['keywords']}"
                )

            # School knowledge
            cursor.execute("""
                SELECT category, question, answer
                FROM chatbot_knowledge
            """)

            records = cursor.fetchall()

            for record in records:
                knowledge.append(
                    f"Category: {record['category']}\n"
                    f"Question: {record['question']}\n"
                    f"Answer: {record['answer']}"
                )

            # School settings
            cursor.execute("""
                SELECT setting_key, setting_value
                FROM school_settings
            """)

            settings = cursor.fetchall()

            for setting in settings:
                knowledge.append(
                    f"{setting['setting_key']}: "
                    f"{setting['setting_value']}"
                )

        return "\n\n".join(knowledge)

    except Exception as error:
        print("Database Error:", error)
        return None

    finally:
        if connection:
            connection.close()
